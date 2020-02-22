<?php

use Main\Library\Json;

class ErrorController extends \Yaf\Controller_Abstract
{
    use Json;

    public function errorAction(\Exception $exception)
    {
        if (preg_match('/\.(?:png|jpg|jpeg|gif|ico)$/', $_SERVER['REQUEST_URI'])) {
            return false;
        }
        $data = [
            'code'     => $exception->getCode(),
            'message'  => $exception->getMessage(),
            'trace'    => $exception->getTrace(),
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
        ];
        debug($data);
        $this->jsonResponse($data['code'], $data['message'], $data['trace']);
        return true;
    }
}
