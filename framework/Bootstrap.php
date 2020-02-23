<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2020-02-23
 * Time: 11:05
 */


namespace Lwenjim\Yaf;


use Lwenjim\Yaf\Manager as DatabaseManager;
use Yaf\Dispatcher;
use Yaf\Plugin_Abstract as Plugin;
use Yaf\Request\Http;
use Yaf\Request_Abstract as Request;
use Yaf\Response_Abstract as Response;

trait Bootstrap
{
    use Json;

    public function _initDatabase(Dispatcher $dispatcher)
    {
        DatabaseManager::getInstance()->init($dispatcher);
    }

    public function _init(Dispatcher $dispatcher)
    {
        $dispatcher->disableView();
        $dispatcher->catchException(false);
        $dispatcher->throwException(true);
    }

    public function _initPlugin(Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new class extends Plugin
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
        });
    }

    public function _initRoute(Dispatcher $dispatcher)
    {
        $dispatcher->getRouter()->addRoute('restfull', new Restful());
        if (file_exists($routeFile = base_path('app/api/route.php'))) {
            $dispatcher->getRouter()->addRoute('Rewrite', new Rewrite(require_once $routeFile, Application::getInstance()->getApp(), ''));
        }
        $dispatcher->setRequest(new class($dispatcher->getRequest()->getRequestUri(), $dispatcher->getRequest()->getBaseUri()) extends Http
        {
            public function getQuery($name = null, $default = null)
            {
                debug($name);
                return parent::getQuery($name, $default);
            }

            public function __construct(string $request_uri, string $base_uri)
            {
                debug(compact('request_uri', 'base_uri'));
                parent::__construct($request_uri, $base_uri);
            }
        });
        $dispatcher->catchException(true);
    }

    public function _initModule()
    {
        foreach (Application::getClassAlias() as $abstruct => $alias) {
            $abstruct::getInstance();
        }
    }
}
