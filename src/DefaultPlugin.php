<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-09-26
 * Time: 10:57
 */

namespace Com\Plugin;

use Yaf\Plugin_Abstract as Plugin;
use Yaf\Request_Abstract as Request;
use Yaf\Response_Abstract as Response;

class DefaultPlugin extends Plugin
{
    public function routerStartup(Request $request, Response $response)
    {
    }

    public function routerShutdown(Request $request, Response $response)
    {
        debug(['url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 'params' => $request->getParams(), 'method' => $request->getMethod()]);
    }

    public function dispatchLoopStartup(Request $request, Response $response)
    {
    }

    public function preDispatch(Request $request, Response $response)
    {
    }

    public function postDispatch(Request $request, Response $response)
    {
    }

    public function dispatchLoopShutdown(Request $request, Response $response)
    {
    }

    public function preResponse(Request $request, Response $response)
    {
    }
}
