<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:28
 */

namespace Main\Map;


trait ServiceTransformerMap
{
    protected function getServiceTransformerMap()
    {
        $map = [];
        $dir = APP_PATH . '/app/api/modules';
        foreach (array_diff(scandir($dir), ['.', '..']) as $moduleName) {
            $subDir = sprintf($dir . '/%s/controllers', $moduleName);
            array_map(function (string $basename) use (&$map, $moduleName) {
                $filename = pathinfo($basename, PATHINFO_FILENAME);
                $filename = $this->getController()->getControllerAlias($filename);
                $cur      = ['Main\Service\\' . $moduleName . '\\' . $filename . 'Service' => 'Main\Transformers\\' . $moduleName . '\\' . $filename . 'Transformer'];
                $map      = array_merge($map, $cur);
            }, array_diff(scandir($subDir), ['.', '..']));
        }
        return $map[static::class];
    }
}
