<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:28
 */

namespace Lwenjim\Yaf;


trait ServiceTransformerMap
{
    protected function getServiceTransformerMap()
    {
        $map = [];
        $dir = base_path() . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getController()->getControllerAlias($filename);
                $cur      = ['\\' . $moduleName . '\\' . $filename . 'Service' => '\\' . $moduleName . '\\' . $filename . 'Transformer'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class];
    }
}
