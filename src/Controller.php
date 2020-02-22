<?php

namespace Lwenjim\Yaf;



abstract class Controller extends \Yaf\Controller_Abstract
{
    use Redis, Validator, Json, ControllerServiceMap, Aop, Request, ControllerModelMap, User;

    final protected function getParam(string $key)
    {
        return $this->getRequest()->getParam($key);
    }

    protected function indexAction()
    {
        try {
            $this->_indexBefore();
            $this->todoCheck();;
            list('params' => $params, 'list' => $list) = $this->getControllerMapService()->index();
            $this->_indexAfter($params, $list);
            $this->jsonResponse(200, '', $list);
        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $this->jsonResponse(500, $exception->getMessage());
        }
    }

    protected function getAction()
    {
        try {
            $this->_getBefore($this);
            $this->todoCheck();;
            list('params' => $params, 'detail' => $detail) = $this->getControllerMapService()->get();
            $this->_getAfter($params, $detail);
            $this->jsonResponse(200, '', $detail);
        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $this->jsonResponse(500, $exception->getMessage());
        }
    }

    protected function postAction()
    {
        try {
            if (empty($this->getParam('todo'))) {
                $this->validatePost($this->getParams());
            } else {
                $this->todoCheck();;
            }
            list('params' => $params, 'model' => $model) = $this->getControllerMapService()->post();
            $this->_postAfter($params, $model);
            $model = $model->toArray();
            $this->jsonResponse(200, $model ? "新增成功" : '', $model);

        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $data = [];
            $msg  = $exception->getMessage();
            if ($exception instanceof NormalException) {
                $data = $exception->getReturn();
                $msg  = '';
            }
            $this->jsonResponse(500, $msg, $data);
        }
    }

    protected function todoCheck()
    {
        $params = $this->getParams();
        if (!empty($this->getParam('todo'))) {
            $method = $this->getParam('todo');
            method_exists($this, $method) and $this->$method($params);
            $service = $this->getControllerMapService();
            if (!method_exists($service, $method)) {
                throw new \Exception('not exists method:' . $method . ' the service of ' . $service->getServiceName());
            }
            $result = call_user_func_array([$service, $method], [$params]);
            $this->jsonResponse(200, '', $result);
            return;
        }
    }

    protected function putAction()
    {
        try {
            if (empty($this->getParam('todo'))) {
                $this->validatePut();
            } else {
                $this->todoCheck();
            }
            list('params' => $params, 'result' => $result, 'model' => $model) = $this->getControllerMapService()->put();
            $this->_putAfter($params, $result, $model);
            $this->jsonResponse(200, $result ? '修改成功' : '', $model);
        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $this->jsonResponse(500, $exception->getMessage());
        }
    }

    protected function validatePut()
    {
        $params = $this->getParams();
        list('rules' => $rules, 'message' => $message) = $this->_putBefore();
        if (empty($rules) || empty($message)) {
            $rules   = $this->getControllerMapModel()::getRules();
            $message = $this->getControllerMapModel()::getMessages();
        }
        $rules = array_map(function ($rule) {
            return array_diff($rule, ['required']);
        }, $rules);
        $rules = array_filter($rules, function ($rule) {
            return !empty($rule);
        });
        if (!empty($rules) && !empty($message)) {
            if (!$this->isBatch()) {
                $params = [$params];
            } else {
                $params = $params['batchUpdate'];
            }
            foreach ($params as $p) {
                $validator = $this->validator()->make($p, $rules, $message);
                $validator->fails() and $this->jsonResponse(500, $this->getValidatorError($validator));
            }
        }
    }

    protected function deleteAction()
    {
        try {
            $this->_deleteBefore($this);
            $this->todoCheck();
            list('result' => $result) = $this->getControllerMapService()->delete();
            $this->jsonResponse(200, $result ? '删除成功' : '');
        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $this->jsonResponse(500, $exception->getMessage());
        }
    }

    final protected function getParams(...$getParams)
    {
        $allParams = $this->getRequest()->getParams();
        if (empty($getParams)) return $allParams;
        $result = array_intersect_key($allParams, $need = array_combine($getParams, $getParams));
        return array_merge($need, $result);
    }

    protected function validatePost($params): void
    {
        list('rules' => $rules, 'message' => $message) = $this->_postBefore();
        if (empty($rules) || empty($message)) {
            $rules   = $this->getControllerMapModel()::getRules();
            $message = $this->getControllerMapModel()::getMessages();
        }
        if (!empty($rules) && !empty($message)) {
            if (!$this->isBatch()) {
                $params = [$params];
            } else {
                $params = $params['batchInsert'];
            }
            foreach ($params as $p) {
                $validator = $this->validator()->make($p, $rules, $message);
                if ($validator->fails()) {
                    $this->jsonResponse(500, $this->getValidatorError($validator));
                }
            }
        }
    }

    public function isBatch()
    {
        return !empty($this->getParam('todo')) && substr($this->getParam('todo'), 0, 5) == 'batch';
    }

    final public function getControllerAlias(String $name)
    {
        $map = [
            'Kmapbaselogic'     => 'KmapBaseLogic',
            'Kmapbaselogicnode' => 'KmapBaseLogicNode',
            'Kmaplocal'         => 'KmapLocal',
            'Kmapbasetree'      => 'KmapBaseTree',
            'Kmaplocalnode'     => 'KmapLocalNode',
            'Comoperatelog'     => 'ComOperateLog',
            'Kmapbasetreenode'  => 'KmapBaseTreeNode',
        ];
        if (isset($map[$name])) {
            return $map[$name];
        }
        return $name;
    }
}
