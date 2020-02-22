<?php

use Lwenjim\Yaf\Application;
use Lwenjim\Yaf\DefaultPlugin;
use Lwenjim\Yaf\Json;
use Lwenjim\Yaf\Restful;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Yaf\Plugin_Abstract as Plugin;
use Yaf\Request\Http;
use Lwenjim\Yaf\Manager as DatabaseManager;
use Yaf\Request_Abstract as Request;
use Yaf\Response_Abstract as Response;

class Bootstrap extends Bootstrap_Abstract
{
    use Json;

    public function _initDatabase(Dispatcher $dispatcher)
    {
        DatabaseManager::getInstance()->init($dispatcher);
    }

    public function _init(Dispatcher $dispatcher)
    {
        $dispatcher->disableView();
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
