<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 2019-07-19
 * Time: 15:11
 */

namespace Main\Transformers;

use League\Fractal\TransformerAbstract;

class Transformer extends TransformerAbstract
{
    protected $validParams = ['limit', 'order'];
}
