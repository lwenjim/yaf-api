<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-23
 * Time: 20:31
 */

namespace Lwenjim\Yaf;

use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait LeagueFractal
{
    protected function getManager()
    {
        static $manager;
        if (empty($manager)) {
            $manager = new Manager();
        }
        if ($this->getParam('include')) {
            $manager->parseIncludes($this->getParam('include'));
        }
        return $manager;
    }

    protected function collection($collection, $transformer)
    {
        $resource = new Collection($collection, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    protected function item($item, $transformer)
    {
        $resource = new Item($item, $transformer);
        return $this->getManager()->createData($resource)->toArray();
    }

    protected function paginator($paginator, $transformer)
    {
        $collections = $paginator->getCollection();
        $resource    = new Collection($collections, $transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        return $this->getManager()->createData($resource)->toArray();
    }
}
