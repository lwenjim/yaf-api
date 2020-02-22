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
    use Instance, Request, Method, Aop;
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

    protected function operateLog($rowId, $action, $remark = '')
    {
    }

    protected function getTableId()
    {
        return 0;
    }

    protected function user(int $userId = 0)
    {
        return $this->getController()->user($userId);
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

    protected function collection($collection, $transformer)
    {
        $resource = new Collection($collection, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    protected function item($item, $transformer)
    {
        $resource = new Item($item, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    protected function paginator($paginator, $transformer)
    {
        $collections = $paginator->getCollection();
        $resource    = new Collection($collections, $transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        return $this->getManager()->createData($resource)->toArray();
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['getRequest'])){
            return call_user_func_array([$this->getController(), $name], $arguments);
        }
    }
}
