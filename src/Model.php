<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-18
 * Time: 15:21
 */

namespace Main\Model;

use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel
{
    const CREATED_AT = 'created_time';

    const UPDATED_AT = 'updated_time';

    const DELETED_AT = 'deleted_time';

    protected static function getEnum(string $text, int $index = null)
    {
        $map = [];
        array_map(function ($fields) use (&$map) {
            list($id, $text) = explode(':', $fields);
            $map[(int)$id] = $text;
        }, array_diff(explode(',', $text), ['.', '..']));
        if (isset($map[$index])) return $map[$index];
        return $map;
    }

    final public static function getRules($keys = [], $except = [])
    {
        $rules = array_map(function ($rule) use ($except) {
            return array_filter($rule, function ($r) use ($except) {
                return $r instanceof In || !in_array($r, $except);
            });
        }, static::getRuleConfig());
        $rules = array_filter($rules, function ($rule) {
            return !empty($rule);
        });
        if (empty($keys)) return $rules;
        return array_intersect_key($rules, array_flip($keys));
    }

    protected static function getRuleConfig(): array
    {
        return [];
    }

    protected static function getComment(): array
    {
        return [];
    }

    public static function getMessages(): array
    {
        return [];
    }
}
