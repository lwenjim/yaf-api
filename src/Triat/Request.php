<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:33
 */

namespace Com;


trait Request
{
    protected function getParam(string $name)
    {
        return $this->getRequest()->getParam($name);
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

    protected function getParams()
    {
        return $this->getRequest()->getParams();
    }

    protected function getAction()
    {
        return $this->getRequest()->getActionName();
    }
}
