<?php

namespace Main\Library;

use Predis\Client;
use Yaf\Registry;


trait Redis
{
    public function redis($channel = 'default')
    {
        static $redis = null;
        if (empty($redis)) {
            $config = Registry::get('redis_ini')->get($channel);
            $pwd    = empty($config->get('auth')) ? [] : ['password' => $config->get('auth'), 'profile' => '2.8', 'prefix' => 'course_v3:'];
            $params = ['database' => (int)$config->get('database'),];
            $params = array_merge($params, $pwd);
            $redis  = new Client($config->get('host'), ['parameters' => $params]);
        }
        return $redis;
    }

    public function getRedisKey($id)
    {
        return "course_v5:{$id}";
    }
}
