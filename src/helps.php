<?php


function debug($data, ?string $key = 'debug')
{
    try {
        $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        JimLog\Log::getInstance()->debug($key . '-' . $data);
    } catch (\Exception|\Error $exception) {

    }
}

function error($data, ?string $key = 'error')
{
    try {
        $data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        JimLog\Log::getInstance()->error($key . '-' . $data);
    } catch (\Exception|\Error $exception) {

    }
}

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

function _array_intersect_key(array $array1, array $array2)
{
    return array_intersect_key($array1, array_combine($array2, $array2));
}

function _array_count_values(array $input)
{
    return array_count_values(array_map(function ($value) {
        return $value . '';
    }, $input));
}
