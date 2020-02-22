<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 5/2/2019
 * Time: 4:14 PM
 */

namespace Lwenjim\Yaf;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Yaf\Application as YafApplication;
use Yaf\Request\Simple;

class Application extends Container
{
    protected        $app                  = null;
    protected        $availableBindings    = ['config' => 'registerConfigBindings',];
    protected        $ranServiceBinders    = [];
    protected        $loadedConfigurations = [];
    protected        $basePath             = "";
    protected        $aliases              = [];
    protected static $container            = [];

    public function getApp(): YafApplication
    {
        return $this->app;
    }

    public function setApp(YafApplication $app)
    {
        $this->app = $app;
        return $this;
    }

    public function __construct($basePath = null)
    {
        try {
            if (!empty(env('APP_TIMEZONE'))) {
                date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
            }
            $this->basePath = $basePath;
            $this->bootstrapContainer();
            $config    = array(
                "application" => array(
                    "directory" => $basePath . '/app/api',
                    "library"   => $basePath . '/src',
                    "modules"   => 'index,api',
                ),
            );
            $this->app = new YafApplication($config);
        } catch (\Exception $exception) {

        }
    }

    protected function bootstrapContainer()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance('Lwenjim\Yaf\Application', $this);

        $this->instance('path', $this->path());

        $this->registerContainerAliases();
    }

    public function path()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    protected function registerContainerAliases()
    {
        $this->aliases = [
            'Illuminate\Contracts\Config\Repository'   => 'config',
            'Illuminate\Container\Container'           => 'app',
            'Illuminate\Contracts\Container\Container' => 'app',
        ];
    }

    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        if (array_key_exists($abstract, $this->availableBindings) && !array_key_exists($this->availableBindings[$abstract], $this->ranServiceBinders)) {
            $this->{$method = $this->availableBindings[$abstract]}();
            $this->ranServiceBinders[$method] = true;
        }
        return parent::make($abstract, $parameters);
    }

    public function configure($name)
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;

        $path = $this->getConfigurationPath($name);

        if ($path) {
            $this->make('config')->set($name, require $path);
        }
    }

    public function getConfigurationPath($name = null)
    {
        if (!$name) {
            $appConfigDir = $this->basePath('config') . '/';

            if (file_exists($appConfigDir)) {
                return $appConfigDir;
            } elseif (file_exists($path = __DIR__ . '/../config/')) {
                return $path;
            }
        } else {
            $appConfigPath = $this->basePath('config') . '/' . $name . '.php';

            if (file_exists($appConfigPath)) {
                return $appConfigPath;
            } elseif (file_exists($path = __DIR__ . '/../config/' . $name . '.php')) {
                return $path;
            }
        }
    }

    public function basePath($path = null)
    {
        if (isset($this->basePath)) {
            return $this->basePath . ($path ? '/' . $path : $path);
        }

        if ($this->runningInConsole()) {
            $this->basePath = getcwd();
        } else {
            $this->basePath = realpath(getcwd() . '/../');
        }

        return $this->basePath($path);
    }

    protected function registerConfigBindings()
    {
        $this->singleton('config', function () {
            return new ConfigRepository;
        });
    }

    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    public function run()
    {
        try {
            $this->getApp()->bootstrap();
            if (!$this->runningInConsole()) {
                $this->getApp()->run();
            } else {
                $this->getApp()->getDispatcher()->dispatch(new Simple());
            }
        } catch (\Exception $exception) {

        }
    }

    public static function getEnv()
    {
        return \Yaf\ENVIRON;
    }

    public static function app(string $key = null, $instance = null)
    {
        if (empty($key)) {
            return self::$container;
        }
        if (empty($instance)) {
            $alias = array_flip(self::getClassAlias());
            if (isset($alias[$key])) {
                $key = $alias[$key];
            }
            return isset(self::$container[$key]) ? self::$container[$key] : false;
        }
        return self::$container[$key] = $instance;
    }

    public static function getClassAlias()
    {
        return [
            Application::class => 'app',
            Http::class        => 'http',
        ];
    }
}
