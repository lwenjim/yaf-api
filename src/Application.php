<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 5/2/2019
 * Time: 4:14 PM
 */

namespace Lwenjim\Yaf;

use Main\Library\Http;
use Main\Library\Instance;
use Yaf\Application as YafApplication;
use Yaf\Request\Simple;

class Application
{
    use Instance;
    protected        $app       = null;
    protected static $container = [];

    public static function app(string $key = null, $instance = null)
    {
        if (empty($key)) {
            return self::$container;
        }
        if (empty($instance)) {
            $alias = array_flip(self::getAlias());
            if (isset($alias[$key])) {
                $key = $alias[$key];
            }
            return isset(self::$container[$key]) ? self::$container[$key] : false;
        }
        return self::$container[$key] = $instance;
    }

    public function getApp(): YafApplication
    {
        return $this->app;
    }

    public function setApp(YafApplication $app)
    {
        $this->app = $app;
        return $this;
    }

    private function __construct()
    {
        try {
            $type      = php_sapi_name() !== 'cli' ? 'api' : 'cli';
            $this->app = new YafApplication($temp = APP_PATH . "/config/basic_{$type}.ini");
        } catch (\Exception $exception) {
            debug('Failed to new YafApplication');
        }
    }

    public function run()
    {
        $this->getApp()->bootstrap();
        if (php_sapi_name() !== 'cli') {
            $this->getApp()->run();
        } else {
            $this->getApp()->getDispatcher()->dispatch(new Simple());
        }
    }

    public static function getEnv()
    {
        return \Yaf\ENVIRON;
    }

    public static function getAlias()
    {
        return [
            Application::class => 'app',
            Http::class        => 'http',
        ];
    }
}
