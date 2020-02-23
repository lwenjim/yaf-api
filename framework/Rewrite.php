<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 5/2/2019
 * Time: 3:47 PM
 */

namespace Lwenjim\Yaf;

use Closure;
use ReflectionClass;
use Yaf\Exception\RouterFailed;
use Yaf\Request_Abstract;
use Yaf\Route_Interface;

class Rewrite implements Route_Interface
{
    protected $rule   = [];
    protected $app    = null;
    protected $prefix = '';

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): Rewrite
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getApp(): \Yaf\Application
    {
        return $this->app;
    }

    public function setApp(\Yaf\Application $app): void
    {
        $this->app = $app;
    }

    public function getRule(string $key): ?Closure
    {
        return $this->rule[$key] ?? null;
    }

    public function setRule(string $key, \Closure $rule): void
    {
        $this->rule[$key] = $rule;
    }

    public function addRule($rules)
    {
        array_walk($rules, function ($rule, $key) {
            $key = strtolower($key);
            $this->setRule($key, function (Request_Abstract $request) use ($rule) {
                list($module, $controller, $action) = $this->parseRule($rule);
                $module     = ucfirst($module);
                $controller = ucfirst($controller);
                $action     = strtolower($action);
                if (!empty($module)) {
                    $this->checkModule($module);
                    $request->setModuleName($module);
                }

                if (!empty($controller)) {
                    $controller = ucfirst($controller);
                    $className  = $this->checkController($module, $controller);
                    $request->setControllerName($controller);
                }

                if (!empty($action)) {
                    $this->checkAction($className ?? '', $action . 'Action');
                    $request->setActionName($action);
                }
                $request->setRouted();
            });
        });
    }

    protected function checkPrefixAndCutPrefix(&$path): bool
    {
        if (empty($this->getPrefix())) {
            return true;
        }
        if (substr($path, 0, strlen($this->getPrefix())) != $this->getPrefix()) {
            return false;
        }
        $path = substr($path, strlen($this->getPrefix()));
        return true;
    }

    protected function parseRule($rule)
    {
        $parts = explode('/', $rule);
        if (count($parts) < 3) {
            $parts = array_pad($parts, -3, null);
        }
        return $parts;
    }

    protected function checkModule($module)
    {
        if (!in_array(ucfirst($module), $this->getApp()->getModules())) {
            throw new RouterFailed(sprintf("not exits the module of %s", $module));
        }
    }

    protected function checkController($module, $controller): string
    {
        $path = $this->getApp()->getAppDirectory();
        if (!empty($module)) {
            $path .= "/modules/" . $module . '/controllers';
        }
        $path .= '/' . $controller . '.php';
        if (!is_file($path) || !file_exists($path)) {
            throw new RouterFailed(sprintf("not exits file:%s", $path), 500);
        }
        require_once $path;
        $className = $controller . 'Controller';
        if (!class_exists($className, false)) {
            throw new RouterFailed(sprintf("not exits the controller of %s", $className), 500);
        }
        return $className;
    }

    protected function checkAction($className, $action)
    {
        try {
            $reflect = new ReflectionClass($className);
            $reflect->getMethod($action);
        } catch (\Exception |\Error $exception) {
            throw new RouterFailed(sprintf("not exits the method of %s in the class %s", $action, $className), 500);
        }
    }

    public function __construct(array $rules, \Yaf\Application $app, string $prefix = null)
    {
        $this->addRule($rules);
        $this->setApp($app);
        $this->setPrefix($prefix);
    }

    public function route($request)
    {
        try{
            list($path, $query) = explode('?', str_replace('//', '/', $_SERVER['REQUEST_URI'])) + [null, null];
            $this->setParams($query, $request);
            if (!$this->checkPrefixAndCutPrefix($path)) {
                return false;
            }
            $closure = $this->getRule(strtolower($path));
            if (null != $closure && is_callable($closure)) {
                $closure($request);
                return true;
            }
        }catch (\Exception $exception){
            return false;
        }
        return false;
    }

    public function assemble(array $info, array $query = NULL)
    {

    }

    protected function setParams($query, Request_Abstract $request)
    {
        $params = [];
        if (!empty($query)) {
            $params = \GuzzleHttp\Psr7\parse_query($query);
        }
        if (!empty($_POST)) {
            $params = array_merge($params, $_POST);
        }
        array_walk($params, function ($value, $key) use ($request) {
            $request->setParam($key, $value);
        });
    }

    protected function parse_uri()
    {

    }
}
