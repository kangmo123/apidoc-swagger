<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/6/6
 * Time: 10:55 AM
 */

namespace App\Services\DataStore;

interface StoreInterface
{
    /**
     * 将数据落入底表
     *
     * @param array  $data 原数据
     *
     * @return bool 是否执行成功
     */
    public function save(array $data);
    
    /**
     * 清理底表数据
     *
     * @return bool 是否执行成功
     */
    public function clean();
}
