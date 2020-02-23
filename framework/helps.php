<?php


use Illuminate\Container\Container;
use Lwenjim\Yaf\Log;

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('app')) {
    function app($make = null)
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($make);
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return app()->basePath() . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('_array_map')) {
    function _array_map(callable $func, array $arr)
    {
        $newArr = [];
        foreach ($arr as $key => $value) {
            [$newValue, $newKey] = $func($value, $key);
            $newKey          = $newKey ? $newKey : $key;
            $newValue        = $newValue ? $newValue : $value;
            $newArr[$newKey] = $newValue;
        }
        return $newArr;
    }
}

if (!function_exists('_array_intersect_key')) {
    function _array_intersect_key(array $array1, array $array2)
    {
        return array_intersect_key($array1, array_combine($array2, $array2));
    }
}

if (!function_exists('debug')) {
    function debug($data, ?string $key = 'debug')
    {
        try {
            $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            Log::getInstance()->debug($key . '-' . $data);
        } catch (\Exception|\Error $exception) {
        }
    }
}
if (!function_exists('error')) {
    function error($data, ?string $key = 'error')
    {
        try {
            $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            Log::getInstance()->error($key . '-' . $data);
        } catch (\Exception|\Error $exception) {
        }
    }
}
