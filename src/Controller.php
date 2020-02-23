<?php

namespace Lwenjim\Yaf;


use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Yaf\Controller_Abstract as ControllerAbstract;

abstract class Controller extends ControllerAbstract
{
    use Json;

    final protected function getParam(string $key)
    {
        return $this->getRequest()->getParam($key);
    }

    protected function indexAction()
    {
        try {
            $this->_indexBefore();
            $this->todoCheck();;
            list('params' => $params, 'list' => $list) = $this->getService()->index();
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
            list('params' => $params, 'detail' => $detail) = $this->getService()->get();
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
            list('params' => $params, 'model' => $model) = $this->getService()->post();
            $this->_postAfter($params, $model);
            $model = $model->toArray();
            $this->jsonResponse(200, $model ? "新增成功" : '', $model);

        } catch (\Exception|\Error $exception) {
            error($exception->getMessage());
            $data = [];
            $msg  = $exception->getMessage();
            $this->jsonResponse(500, $msg, $data);
        }
    }

    protected function todoCheck()
    {
        $params = $this->getParams();
        if (!empty($this->getParam('todo'))) {
            $method = $this->getParam('todo');
            method_exists($this, $method) and $this->$method($params);
            $service = $this->getService();
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
            list('params' => $params, 'result' => $result, 'model' => $model) = $this->getService()->put();
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
            $rules   = $this->getModel()::getRules();
            $message = $this->getModel()::getMessages();
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
            list('result' => $result) = $this->getService()->delete();
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
            $rules   = $this->getModel()::getRules();
            $message = $this->getModel()::getMessages();
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

    public function getService():Service
    {
        $map = [];
        $dir = base_path() . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getControllerAlias($filename);
                $cur      = [$filename . "Controller" => '\\Lwenjim\\App\\Services\\' . $moduleName . '\\' . $filename . 'Service'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class]::getInstance($this);
    }

    public function getModel()
    {
        $map = [];
        $dir = base_path() . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getControllerAlias($filename);
                $cur      = [$filename . "Controller" => '\\Lwenjim\\App\\Models\\' . $moduleName . '\\' . $filename . 'Model'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class];
    }

    public function validator()
    {
        static $validator = null;
        if (empty($validator)) {
            $validator = new Factory(new Translator(new ArrayLoader(), 'Translator'));
        }
        return $validator;
    }

    public function getValidatorError($validator)
    {
        $errors = $validator->errors()->toArray();
        foreach ($errors as $k => $v){
            if ($this->checkHasCn($v)){
                $message[] =  $v[0];
            }else{
                $message[] = "参数（{$k}）:" . $v[0];
            }
        }
        return implode("--", $message ?? []);
    }

    private function checkHasCn($str): bool
    {
        if ($ret = preg_grep("/[\x{4e00}-\x{9fff}]/u", $str)) {
            return true;
        } else {
            return false;
        }
    }

    protected function setParam(string $name, $value = null)
    {
        return $this->getRequest()->setParam($name, $value);
    }

    protected function unsetParam($name)
    {
        (function () use ($name) {
            if (($list = explode('.', $name)) > 1) {
                $val  = '$this->params' . "['" . implode("']['", $list) . "']";
                $eval = "if (isset({$val})) unset({$val});";
                eval($eval);
            } else {
                if (isset($this->params[$name])) unset($this->params[$name]);
            }
        })->call($this->getRequest());
    }

    protected function _indexBefore()
    {
    }

    protected function _indexAfter(&$params, &$list)
    {
    }

    protected function _getBefore($params)
    {
    }

    protected function _getAfter(&$params, &$detail)
    {
    }

    protected function _postBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _postAfter(&$params, $model)
    {

    }

    protected function _putBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _putAfter(&$params, $result, $model)
    {
    }

    protected function _patchBefore(&$params)
    {
    }

    protected function _patchAfter(&$params, &$result)
    {
    }

    protected function _deleteBefore(&$params)
    {
    }

    protected function _deleteAfter(&$params, &$result)
    {
    }
}
