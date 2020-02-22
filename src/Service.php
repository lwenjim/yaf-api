<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-18
 * Time: 15:33
 */

namespace Main\Service;


use Com\Controller;
use Main\Library\Database\Capsule\Manager as DatabaseManager;
use Main\Library\Instance;
use Main\Library\Redis;
use Main\Map\ServiceModelMap;
use Main\Map\ServiceTransformerMap;
use Main\Model\Course\BasicModel;
use Main\Model\Course\ChapterModel;
use Main\Model\Course\KmapRddModel;
use Main\Model\Course\KnowledgeRangeModel;
use Main\Model\Kmap\ComOperateLogModel;
use Main\Model\Kmap\KmapBaseLogicNodeModel;
use Main\Model\Kmap\KmapLocalNodeModel;
use Main\Model\Kmap\KmapLocalPreNodeModel;
use Main\Model\Kmap\KmapModel;
use Main\Yaf\Config;

abstract class Service
{
    use Instance, ServiceTransformerMap, ServiceModelMap, Request, Method, LeagueFractal, NotifyAlgo, Aop, Redis, GetUploadExcel, SearchKnowleage, CallTopic;
    protected $controller;

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function setController(Controller $abstract)
    {
        $this->controller = $abstract;
        return $this;
    }

    public function __construct(Controller $abstract)
    {
        $this->setController($abstract);
    }

    public function getServiceName()
    {
        return static::class;
    }

    protected function useridTransferUserName(array &$list): array
    {
        if (!empty($list['data'])) {
            $userIds  = array_column($list['data'], 'created_user');
            $userIds  = array_merge($userIds, array_column($list['data'], 'updated_user'));
            $userIds  = array_unique($userIds);
            $userList = \Main\Model\User::whereIn('user_id', $userIds)->get()->toArray();
            $userMap  = _array_map(function ($user) {
                return [&$user, $user['user_id']];
            }, $userList);
            array_walk($list['data'], function (&$node) use ($userMap) {
                if (!isset($userMap[$node['created_user']])) return;
                $node['created_user'] = $userMap[$node['created_user']];
                $node['updated_user'] = $userMap[$node['updated_user']];
            });
        }
        return $list;
    }

    public static function fillter($model, array $params)
    {
        $fillable = $model->getFillable();
        $fillable = array_flip($fillable);
        foreach ($params as $k => $v) {
            if (!isset($fillable[$k]))
                unset($params[$k]);
        }
        return $params;
    }

    protected function operateLog($rowId, $action, $remark = '')
    {
        if ($this->getTableId() <= 0 || !in_array($this->getTableId(), ComOperateLogModel::getTableIds())) return;
        OperateLog::getInstance()->log($this->getTableId(), $rowId, $action, $remark);
    }

    protected function getTableId()
    {
        return 0;
    }

    protected function getRank($localId, $key)
    {
        $redisKey = Config::getInstance()->get('kmaplocalnoderank') . ':' . $localId;
        return $this->redis()->zRank($redisKey, $key);
    }

    protected function setRank($localId, $score, $name)
    {
        $prefix       = Config::getInstance()->get('kmaplocalnoderank');
        $redisKeyZset = $prefix . ':' . $localId;
        $redisKeySet  = $prefix . ':Set:' . $localId;
        $this->redis()->zadd($redisKeyZset, $score, $name);
        $this->redis()->sadd($redisKeySet, $score);
    }

    protected function existsScore($localId, $score)
    {
        $prefix      = Config::getInstance()->get('kmaplocalnoderank');
        $redisKeySet = $prefix . ':Set:' . $localId;
        return $this->redis()->sismember($redisKeySet, $score);
    }

    protected function user(int $userId = 0)
    {
        return $this->getController()->user($userId);
    }

    protected function _putAfter(array &$params, $result, $model)
    {
        $this->operateLog($model->id, ComOperateLogModel::ACTION_UPDATE);
    }

    protected function _postAfter(array &$params, $model)
    {
        $this->operateLog($model->id, ComOperateLogModel::ACTION_INSERT);
    }

    protected function _deleteAfter(array &$params, int &$result)
    {
        $this->operateLog($params['id'], ComOperateLogModel::ACTION_DELETE);
    }

    protected function checkCloseLoopRecursive($node, $pre, $mainNode, &$history)
    {
        if (!in_array($pre, $history) && $node != $pre && isset($mainNode[$pre]) && !empty($mainNode[$pre])) {
            if (in_array($node, $mainNode[$pre])) {
                return false;
            }
            $history[] = $pre;
            foreach ($mainNode[$pre] as $ppre) {
                if (false == $this->checkCloseLoopRecursive($node, $ppre, $mainNode, $history)) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function checkCloseLoop($map, $knowledgeCode = null)
    {
        $map      = array_filter($map, function ($m) {
            return !empty($m['pre_lo_list']);
        });
        $mainNode = _array_map(function ($node) {
            return [array_diff(is_array($node['pre_lo_list']) ? $node['pre_lo_list'] : explode(',', $node['pre_lo_list']), [$node['knowledge_code']]), $node['knowledge_code']];
        }, $map);
        foreach ($map as $node) {
            $history = [];
            $pres    = is_array($node['pre_lo_list']) ? $node['pre_lo_list'] : explode(',', $node['pre_lo_list']);
            if ($knowledgeCode != null && $node['knowledge_code'] == $knowledgeCode) {
                foreach ($pres as $pre) {
                    if (!$this->checkCloseLoopRecursive($node['knowledge_code'], $pre, $mainNode, $history)) {
                        return false;
                    }
                }
            } elseif ($knowledgeCode === null) {
                foreach ($pres as $pre) {
                    if (!$this->checkCloseLoopRecursive($node['knowledge_code'], $pre, $mainNode, $history)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    protected function tipsInjectUpdateLocalMap($localId, $codes): void
    {
        $courseIds = KmapRddModel::where(['local_kmap_id' => $localId])->get()->toArray();
        if (!empty($courseIds)) {
            $courseCount  = BasicModel::where('status', '>', 400)->whereIn('id', $courseIds)->count();
            $chapterCount = ChapterModel::where('status', '>', 400)->whereIn('course_id', $courseIds)->count();
            if ($courseCount > 0 || $chapterCount > 0) {
                throw new \Exception(sprintf('知识点%s等逻辑关系已被交付课程或章使用，不可修改！', implode(',', $codes)));
            }
        }
    }

    protected function refreshRank($subjectId, $localId)
    {
        $this->notifyDpoolRefreshRank(self::$notifyOpUpdate, $subjectId, $localId);
    }

    protected function getKnowledgeRangeByLocalIds($localId, &$courses = [], &$chapters = []): array
    {
        $rangeData = [];
        $kmapRdd   = KmapRddModel::whereIn('local_kmap_id', is_array($localId) ? $localId : [$localId])->get()->toArray();
        if (!empty($kmapRdd)) {
            $courseIds        = array_column($kmapRdd, 'course_id');
            $courses          = BasicModel::whereIn('id', $courseIds)->where('status', '>', 400)->get()->toArray();
            $deliverCourseIds = array_column($courses, 'id');
            $rdd              = array_filter($kmapRdd, function ($rdd) use ($deliverCourseIds) {
                return in_array($rdd['course_id'], $deliverCourseIds);
            });
            $rangeIds         = array_column($rdd, 'knowledge_range_id');
            if ($unDeliverCourseIds = array_diff($courseIds, $deliverCourseIds)) {
                $chapters = ChapterModel::where('status', '>', 400)->whereIn('course_id', $unDeliverCourseIds)->get()->toArray();
                $rangeIds += array_column($chapters, 'knowledge_range_id');
            }
            KnowledgeRangeModel::whereIn('id', array_unique($rangeIds))->get()->each(function ($model) use (&$rangeData) {
                if (empty($model->knowledge_range_data)) return;
                $rangeData = array_merge($rangeData, (array)json_decode($model->knowledge_range_data, true));
            });
        }
        return $rangeData;
    }

    protected function updateLogicRelationDo(string $subjectId, string $logicId, $localIds)
    {
        $insertPre     = [];
        $where         = ['subject_id' => $subjectId, 'logic_id' => $logicId, 'local_id' => $localIds, 'status' => 1];
        $rangeData     = $this->getKnowledgeRangeByLocalIds($localIds, $courses, $chapters);
        $handleNodeIds = [];
        KmapLocalNodeModel::where($where)->chunk(100, function ($nodesObject) use ($where, &$insertPre, $rangeData, $handleNodeIds) {
            $nodes = array_column($nodesObject->toArray(), 'knowledge_code');
            if (empty($nodes = array_diff($nodes, $rangeData))) return;
            KmapBaseLogicNodeModel::where(array_diff_key($where, ['local_id' => '']))->whereIn('knowledge_code', $nodes)->chunk(100, function ($group) use ($where, &$insertPre, $handleNodeIds) {
                $group->each(function ($model) use ($where, &$insertPre, $handleNodeIds) {
                    $preString = '';
                    $node      = KmapLocalNodeModel::where($where + ['knowledge_code' => $model->knowledge_code])->first();
                    if (!empty($model->pre_lo_list)) {
                        $pre       = KmapLocalNodeModel::where($where)->whereIn('knowledge_code', explode(',', $model->pre_lo_list))->get()->toArray();
                        $temp      = array_map(function ($prenode) use ($model) {
                            $header = ['subject_id', 'logic_id', 'local_id', 'knowledge_name', 'knowledge_code'];
                            return _array_intersect_key($prenode, $header) + ['local_node_id' => $model->id] + $this->getCommonFields();
                        }, $pre);
                        $insertPre = array_merge($insertPre, $temp);
                        $preString = implode(',', array_column($pre, 'knowledge_code'));
                    }
                    $node->update(['pre_lo_list' => $preString]);
                    $handleNodeIds[] = $node->id;
                });
            });
        });
        KmapLocalPreNodeModel::whereIn('id', $handleNodeIds)->delete();
        !empty($insertPre) && KmapLocalPreNodeModel::insert($insertPre);
        $this->log($localIds);
        $this->notifyAlgoLomap(self::$notifyOpUpdate, $subjectId, $localIds);
        return compact('courses', 'chapters');
    }

    protected function log($id, $type = ComOperateLogModel::ACTION_UPDATE)
    {

    }

    protected function kmapDatabase()
    {
        return DatabaseManager::connection('KmapConnection');
    }

    protected function getDeliveredNodeByLocalId(int $localId)
    {
        $kmapRdd = KmapRddModel::where(['local_kmap_id' => $localId])->get()->toArray();
        if (!empty($kmapRdd)) {
            $courses  = BasicModel::whereIn('id', array_column($kmapRdd, 'course_id'))->where('status', '>', 400)->get()->toArray();
            $chapters = ChapterModel::whereIn('course_id', array_column($kmapRdd, 'course_id'))->where('status', '>', 400)->get()->toArray();
            $range    = array_column($chapters, 'knowledge_range_id') + array_column(array_filter($kmapRdd, function ($rdd) use ($courses) {
                    return in_array($rdd['course_id'], array_column($courses, 'id'));
                }), 'knowledge_range_id');
            if (!empty($range)) {
                $ranges = KnowledgeRangeModel::whereIn('id', $range)->get()->toArray();
                $deviNode    = [];
                foreach ($ranges as $row) {
                    $deviNode += json_decode($row['knowledge_range_data'], true);
                }
                return $deviNode;
            }
        }
        return [];
    }
}
