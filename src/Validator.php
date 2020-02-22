<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-21
 * Time: 00:21
 */

namespace Lwenjim\Yaf;


use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

trait Validator
{
    public function validator()
    {
        static $validator = null;
        if (empty($validator)) {
            $validator = new Factory(new Translator(new ArrayLoader(), 'Translator'));
        }
        return $validator;
    }

    public function getValidatorError($validator)
    {
        $errors = $validator->errors()->toArray();
        foreach ($errors as $k => $v){
            if ($this->checkHasCn($v)){
                $message[] =  $v[0];
            }else{
                $message[] = "参数（{$k}）:" . $v[0];
            }

        }

        return implode("--", $message ?? []);
    }

    private function checkHasCn($str): bool
    {
        if ($ret = preg_grep("/[\x{4e00}-\x{9fff}]/u", $str)) {
            return true;
        } else {
            return false;
        }
    }
}
