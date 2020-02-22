<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:04
 */

namespace Main\Map;


trait ControllerServiceMap
{
    public function getControllerMapService()
    {
        $map = [];
        $dir = APP_PATH . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getControllerAlias($filename);
                $cur      = [$filename . "Controller" => 'Main\Service\\' . $moduleName . '\\' . $filename . 'Service'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class]::getInstance($this);
    }
}
