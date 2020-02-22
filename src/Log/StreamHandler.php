<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-11
 * Time: 10:52
 */

namespace Lwenjim\Yaf\Log;


class StreamHandler extends \Monolog\Handler\StreamHandler
{
    protected function write(array $record)
    {
        [, $theDate] = explode('-', pathinfo($this->getUrl(), PATHINFO_FILENAME), 2);
        if ($theDate != ($currentDate = date('Y-m-d'))) {
            unset($this->stream);
            $this->url = str_replace($theDate, $currentDate, $this->getUrl());
        }
        parent::write($record);
    }
}
