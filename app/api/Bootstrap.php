<?php

use Lwenjim\Yaf\Application;
use Lwenjim\Yaf\DefaultPlugin;
use Lwenjim\Yaf\Json;
use Lwenjim\Yaf\Restful;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Yaf\Request\Http;
use Lwenjim\Yaf\Manager as DatabaseManager;

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
        $dispatcher->registerPlugin(new DefaultPlugin());
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
