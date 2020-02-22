<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-21
 * Time: 10:36
 */

namespace Main\Library;


use Main\Model\Kmap\ComOperateLogModel;
use Main\Service\OperateLog;

trait Json
{
    final public function jsonResponse($code, $msg = '', $data = [])
    {
        $response = [
            'code' => $code,
            'msg'  => $msg,
        ];

        if (isset($data['data'])) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
        $response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        header('Content-Type:application/json; charset=utf-8');
        echo $response;
        exit;
    }
}
