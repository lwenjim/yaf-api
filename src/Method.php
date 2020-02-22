<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:34
 */

namespace Lwenjim\Yaf;



trait Method
{
    public function index()
    {
        $params = $this->getParams();
        $this->_indexBefore($params);
        $class  = $this->getServiceTransformerMap();
        $column = $this->getModelColumn();
        if (!method_exists($this, 'getServiceModelMapForIndex')) {
            $builder = $this->getServiceModelMap()::whereForce($column, $params);
        } else {
            $builder = $this->getServiceModelMapForIndex()->whereForce($column, $params);
        }
        if (isset($params['withTrashed'])) {
            $builder = $builder->withTrashed();
        }
        $params['order'] = $params['order'] ?? 'id,desc';
        foreach (explode('|', $params['order']) as $orderBy) {
            $builder->orderBy(...explode(',', $orderBy));
        }
        $pageSize = isset($params['page_size']) ? $params['page_size'] : KmapModel::PAGESIZE;
        $pageSize > KmapModel::MAX_PAGESIZE and $pageSize = KmapModel::MAX_PAGESIZE;
        $data = $builder->paginate($pageSize, ['*'], 'page', $params['page'] ?? 1);
        $list = $this->paginator($data, new $class);
        $this->_indexAfter($params, $list);
        return compact('params', 'list');
    }

    protected function getModelColumn()
    {
        $classModel = $this->getServiceModelMap();
        $modelObj   = new $classModel;
        $connection = Manager::getInstance()->getDatabaseManager()->connection($modelObj->getConnectionName());
        $sql        = sprintf('show columns from %s', $connection->getTablePrefix() . $modelObj->getTable());
        return $connection->getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function get()
    {
        $params = $this->getParams();
        $this->_getBefore($params);
        $class  = $this->getServiceTransformerMap();
        if (isset($params['withTrashed'])) {
            $model  = $this->getServiceModelMap()::withTrashed()->findOrFail($params['id']);
        }else{
            $model  = $this->getServiceModelMap()::findOrFail($params['id']);
        }
        $detail = $this->item($model, new $class)['data'];
        $this->_getAfter($params, $detail);
        return compact('params', 'detail');
    }

    public function post()
    {
        $params = $this->getParams();
        $this->_postBefore($params);
        $class    = $this->getServiceModelMap();
        $modelObj = new $class;
        $column   = $this->getModelColumn();
        $params   = $params + $this->getCommonFields();

        $data = array_intersect_key($params, array_flip($column));
        if (!empty($this->getParams()['parent_data'])) {
            $model = $modelObj->getParent()->create($params['parent_data'])->children()->create($data);
        } else {
            $model = $modelObj->create($data);
        }
        $this->_postAfter($params, $model);
        return compact('params', 'model');
    }

    public function put()
    {
        $params = $this->getParams();
        $this->_putBefore($params);
        $model  = $this->getServiceModelMap()::find($params['id']);
        $column = $this->getModelColumn();
        $column = ['updated_time' => time(), 'updated_user' => $this->user()->user_id] + $column;
        $data   = array_intersect_key($params, array_flip($column));
        if (!empty($this->getParams()['parent_data'])) {
            $model->getParent()->update($this->getParams()['parent_data']);
        }
        $result = $model->update($data);
        $this->_putAfter($params, $result, $model);
        return compact('params', 'result', 'model');
    }

    public function batchInsert()
    {
        $params = $this->getParams();
        $this->_batchInsertBefore($params);
        $class    = $this->getServiceModelMap();
        $modelObj = new $class;
        $column   = $this->getModelColumn();
        $data     = array_map(function ($p) use ($column) {
            return $data = array_intersect_key($p, array_flip($column)) + ['subject_id' => $this->getParam('subject_id')];
        }, $params['batchInsert']);
        if (!empty($this->getParams()['parent_data'])) {
            $parentModel = $modelObj->getParent()->create($params['parent_data']);
            $data        = array_map(function ($d) use ($parentModel) {
                return $d + ['parent_id' => $parentModel->id];
            }, $data);
            $affectRow   = $this->getServiceModelMap()::insert($data);
        } else {
            $data      = array_map(function ($d) {
                return $d + $this->getCommonFields();
            }, $data);
            $affectRow = 0;
            foreach (array_chunk($data, 100) as $group) {
                $affectRow += $this->getServiceModelMap()::insert($group);
            }
        }
        $this->_batchInsertAfter($params, $affectRow);
        return compact('params', 'affectRow');
    }

    public function batchUpdate()
    {
        $params = $this->getParams();
        $this->_batchUpdateBefore($params);
        $column = $this->getModelColumn();
        $column = ['updated_time' => time(), 'updated_user' => $this->user()->user_id] + $column;
        $result = [];
        $models = [];
        array_map(function ($p) use ($column, $params, &$result, &$models) {
            $data  = array_intersect_key($p, array_flip($column));
            $model = $this->getServiceModelMap()::where(['id' => $p['id'], 'logic_id' => $params['logic_id']])->first();
            if (!empty($params['parent_data'])) {
                $model->getParent()->update($this->getParams()['parent_data']);
            }
            $result[] = $model->update($data);
            $models[] = $model;
        }, $params['batchUpdate']);
        $this->_batchUpdateAfter($params, $result, $models);
        return compact('params', 'result', 'models');
    }

    public function delete()
    {
        $params = $this->getParams();
        $this->_deleteBefore($params);
        $ids = [$params['id']];
        if (!empty($params['ids'])) {
            if (is_string($params['ids'])) {
                $params['ids'] = explode(',', $params['ids']);
            }
            $ids = array_merge($params['ids'], $ids);
            $ids = array_diff($ids, ['', null]);
            $ids = array_unique($ids);
        }
        $result = $this->getServiceModelMap()::whereIn('id', $ids)->delete();
        $this->_deleteAfter($params, $result);
        return compact('params', 'result');
    }

    protected function getCommonFields()
    {
        return ['created_user' => $this->user()->user_id, 'updated_user' => $this->user()->user_id, 'status' => 1, 'subject_id' => $this->getParam('subject_id')];
    }
}
