<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-09-20
 * Time: 10:00
 */

namespace Lwenjim\Yaf;

use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Events\Dispatcher;
use Yaf\Dispatcher as YafDispatcher;

class Manager extends CapsuleManager
{
    use Instance;
    protected $dispatcher;

    public function init(YafDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->database();
    }

    public function database()
    {
        $this->addConnection(config('database.connections.mysql'));
        $this->setEventDispatcher(new Dispatcher($this->getContainer()));
        $this->setAsGlobal();
        $this->bootEloquent();
        $this->initWhereForce();
        $this->getDatabaseManager()->listen(function ($query) {
            debug(["sql" => $this->bindParamsTosql($query->sql, $query->bindings)]);
        });
    }

    public function initWhereForce()
    {
        Builder::macro('whereForce', function ($column, $params) {
            $in  = [];
            $raw = [];
            !empty($params['raw']) and $this->whereRaw($params['raw']);
            foreach ($params as $key => $value) {
                if (substr_count($key, ',') == 1) {
                    [$field, $operate] = explode(',', $key);
                    $map = ['gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<='];
                    in_array($field, $column) and isset($map[$operate]) and $this->where($field, $map[$operate], $value);
                    $operate == 'in' and $this->whereIn($field, explode(',', $value));
                    $operate == 'notin' and $this->whereNotIn($field, explode(',', $value));
                }
            }
            $where = array_intersect_key($params, array_flip($column));
            !empty($where) && array_walk($where, function ($w, $key) use (&$in, &$where, &$raw) {
                if (is_array($w)) {
                    $in[$key] = $w;
                    unset($where[$key]);
                }
            });
            !empty($in) && array_walk($in, function ($i, $key) {
                $this->whereIn($key, $i);
            });
            return $this->where($where);
        });
    }

    protected function bindParamsTosql($sql, $params)
    {
        if (empty($params)) return $sql;
        $sql    = str_replace('?', "'%s'", $sql);
        $prefix = 'a' . $this->randomString(30);
        extract($params, EXTR_PREFIX_ALL, $prefix);
        $vars = array_filter(array_keys(get_defined_vars()), function ($key) use ($prefix) {
            return strpos($key, $prefix) === 0;
        });

        //替换右边
        $lChar = $this->randomString(30);
        $sql   = str_replace("%'", $lChar, $sql);
        $mChar = $this->randomString(30);
        $sql   = str_replace("%s", $mChar, $sql);

        //替换左边
        $rChar = $this->randomString(30);
        $sql   = str_replace("'%", $rChar, $sql);
        $sql   = str_replace($mChar, "%s", $sql);
        $eval  = '$sql = sprintf($sql, $' . implode(', $', $vars) . ');';
        eval($eval);
        $sql = str_replace($lChar, "%'", $sql);
        $sql = str_replace($rChar, "'%", $sql);
        return $sql;
    }

    public function randomString($length): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
