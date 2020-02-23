<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-18
 * Time: 15:33
 */

namespace Lwenjim\Yaf;


use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

abstract class Service
{
    use Instance;
    protected $controller;

    public function __construct(?Controller $abstract = null)
    {
        $this->setController($abstract);
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

    public function getServiceName()
    {
        return static::class;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['getRequest'])) {
            return call_user_func_array([$this->getController(), $name], $arguments);
        }
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function setController(?Controller $abstract)
    {
        $this->controller = $abstract;
        return $this;
    }

    public function index()
    {
        $params = $this->getParams();
        $this->_indexBefore($params);
        $class  = $this->getTransformer();
        $column = $this->getModelColumn();
        if (!method_exists($this, 'getServiceModelMapForIndex')) {
            $builder = $this->getModel()::whereForce($column, $params);
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
        $pageSizeDefualt = config('config.MAX_PAGESIZE');
        $pageSize        = isset($params['page_size']) ? $params['page_size'] : $pageSizeDefualt;
        $pageSize > $pageSizeDefualt and $pageSize = $pageSizeDefualt;
        $data = $builder->paginate($pageSize, ['*'], 'page', $params['page'] ?? 1);
        $list = $this->paginator($data, new $class);
        $this->_indexAfter($params, $list);
        return compact('params', 'list');
    }

    protected function getTransformer()
    {
        $map = [];
        $dir = base_path() . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getController()->getControllerAlias($filename);
                $cur      = ['Lwenjim\\App\\Services\\' . $moduleName . '\\' . $filename . 'Service' => '\\Lwenjim\\App\\Transformers\\' . $moduleName . '\\' . $filename . 'Transformer'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        //var_dump([$map, static::class]);exit;
        return $map[static::class];
    }

    protected function getModelColumn()
    {
        $classModel = $this->getModel();
        $modelObj   = new $classModel;
        $connection = \Lwenjim\Yaf\Manager::getInstance()->getDatabaseManager()->connection($modelObj->getConnectionName());
        $sql        = sprintf('show columns from %s', $connection->getTablePrefix() . $modelObj->getTable());
        return $connection->getPdo()->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function getModel()
    {
        $map = [];
        $dir = base_path() . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getController()->getControllerAlias($filename);
                $cur      = ['Lwenjim\\App\\Services\\' . $moduleName . '\\' . $filename . 'Service' => '\\Lwenjim\\App\\Models\\' . $moduleName . '\\' . $filename . 'Model'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class];
    }

    protected function paginator($paginator, $transformer)
    {
        $collections = $paginator->getCollection();
        $resource    = new Collection($collections, $transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        return $this->getManager()->createData($resource)->toArray();
    }

    public function get()
    {
        $params = $this->getParams();
        $this->_getBefore($params);
        $class = $this->getTransformer();
        if (isset($params['withTrashed'])) {
            $model = $this->getModel()::withTrashed()->findOrFail($params['id']);
        } else {
            $model = $this->getModel()::findOrFail($params['id']);
        }
        $detail = $this->item($model, new $class)['data'];
        $this->_getAfter($params, $detail);
        return compact('params', 'detail');
    }

    protected function item($item, $transformer)
    {
        $resource = new Item($item, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    public function post()
    {
        $params = $this->getParams();
        $this->_postBefore($params);
        $class    = $this->getModel();
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

    protected function getCommonFields()
    {
        return ['created_user' => $this->user()->user_id, 'updated_user' => $this->user()->user_id, 'status' => 1, 'subject_id' => $this->getParam('subject_id')];
    }

    protected function user(int $userId = 0)
    {
        return $this->getController()->user($userId);
    }

    public function put()
    {
        $params = $this->getParams();
        $this->_putBefore($params);
        $model  = $this->getModel()::find($params['id']);
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
        $class    = $this->getModel();
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
            $affectRow   = $this->getModel()::insert($data);
        } else {
            $data      = array_map(function ($d) {
                return $d + $this->getCommonFields();
            }, $data);
            $affectRow = 0;
            foreach (array_chunk($data, 100) as $group) {
                $affectRow += $this->getModel()::insert($group);
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
            $model = $this->getModel()::where(['id' => $p['id'], 'logic_id' => $params['logic_id']])->first();
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
        $result = $this->getModel()::whereIn('id', $ids)->delete();
        $this->_deleteAfter($params, $result);
        return compact('params', 'result');
    }

    protected function operateLog($rowId, $action, $remark = '')
    {
    }

    protected function getTableId()
    {
        return 0;
    }

    protected function collection($collection, $transformer)
    {
        $resource = new Collection($collection, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    protected function getManager()
    {
        static $manager;
        if (empty($manager)) {
            $manager = new Manager();
        }
        if ($this->getParam('include')) {
            $manager->parseIncludes($this->getParam('include'));
        }
        return $manager;
    }

    protected function _indexBefore()
    {
    }

    protected function _indexAfter(&$params, &$list)
    {
    }

    protected function _getBefore($params)
    {
    }

    protected function _getAfter(&$params, &$detail)
    {
    }

    protected function _postBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _postAfter(&$params, $model)
    {

    }

    protected function _putBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _putAfter(&$params, $result, $model)
    {
    }

    protected function _patchBefore(&$params)
    {
    }

    protected function _patchAfter(&$params, &$result)
    {
    }

    protected function _deleteBefore(&$params)
    {
    }

    protected function _deleteAfter(&$params, &$result)
    {
    }
}
