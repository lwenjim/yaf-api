<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-09-19
 * Time: 17:44
 */

namespace Com;


trait Aop
{
    protected function _indexBefore()
    {
    }

    protected function _indexAfter(array &$params, array &$list)
    {
    }

    protected function _getBefore(Controller $controller)
    {
    }

    protected function _getAfter(array &$params, array &$detail)
    {
    }

    protected function _postBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _postAfter(array &$params, $model)
    {

    }

    protected function _putBefore(): array
    {
        return ['rules' => [], 'message' => []];
    }

    protected function _putAfter(array &$params, $result, $model)
    {
    }

    protected function _patchBefore(array &$params)
    {
    }

    protected function _patchAfter(array &$params, array &$result)
    {
    }

    protected function _deleteBefore(Controller $controller)
    {
    }

    protected function _deleteAfter(array &$params, int &$result)
    {
    }
}
