<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-18
 * Time: 10:43
 */

namespace Main\Yaf\Route;

use Yaf\Exception as YafException;
use Yaf\Route_Interface;
use Yaf\Request_Abstract as Request;

class Restful implements Route_Interface
{
    public function route(Request $request)
    {
        $overRide = getallheaders();
        if (isset($overRide['X-Http-Method-Override'])) {
            $overRide['x-http-method-override'] = $overRide['X-Http-Method-Override'];
        } else if (isset($overRide['X_HTTP_METHOD_OVERRIDE'])) {
            $overRide['x-http-method-override'] = $overRide['X_HTTP_METHOD_OVERRIDE'];
        }
        $method = function_exists('getallheaders') && isset($overRide['x-http-method-override']) ? $overRide['x-http-method-override'] : $request->getMethod();
        $method = strtolower($method);
        if (!in_array($method, array('get', 'post', 'put', 'delete'))) {
            throw new YafException("{$method} not supported", 405);
        }
        $path = $_SERVER['REQUEST_URI'];
        if (($pos = strpos($path, '?')) != false) {
            $path = substr($path, 0, $pos);
        }
        $path = $path == '/' ? "kmap/kmapbaselogic/index" : $path;
        list($module, $control, $param1, $param2) = ($path == '/' ? [] : explode('/', ltrim($path, '/'))) + ['', '', $method, 0];
        $id     = is_numeric($param1) ? $param1 : $param2;
        $method = (is_numeric($param1) || empty($param1)) ? $method : $param1;
        $action = empty($id) && $method == "get" ? "index" : $method;
        $id > 0 && $request->setParam("id", $id);
        $this->initParam($request, $overRide);

        $request->setModuleName($module);
        $request->setControllerName(ucfirst(strtolower($control)));
        $request->setActionName($action);
        $request->setRouted();
        return true;
    }

    public function assemble(array $info, array $query = null)
    {

    }

    public function initParam($request, $headers): void
    {
        $query  = $_SERVER['QUERY_STRING'];
        $params = [];
        if (!empty($query)) {
            $params = \GuzzleHttp\Psr7\parse_query($query);
        }
        if (!empty($_POST)) {
            $params = array_merge($params, $_POST);
        }
        if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json') {
            $params += json_decode(file_get_contents('php://input'), true);
        }
        is_array($params) && array_walk($params, function ($value, $key) use ($request) {
            $request->setParam($key, $value);
        });
    }
}
