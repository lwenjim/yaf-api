<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:04
 */

namespace Lwenjim\Yaf;


trait ControllerModelMap
{
    public function getControllerMapModel()
    {
        $map = [];
        $dir = APP_PATH . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getControllerAlias($filename);
                $cur      = [$filename . "Controller" => '' . $moduleName . '\\' . $filename . 'Model'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class];
    }
}
