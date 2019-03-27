<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/7/15
 * Time: 5:43 PM
 */

namespace App\Repositories;

trait SelfName
{
    public function className(): string
    {
        return self::class;
    }
}
