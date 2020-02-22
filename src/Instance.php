<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-19
 * Time: 12:21
 */

namespace Lwenjim\Yaf;



trait Instance
{
    public static function getInstance(...$params): self
    {
        $className = get_called_class();
        if (!Application::app($className)) {
            Application::app($className, new static(...$params));
        }
        return Application::app($className);
    }
}
