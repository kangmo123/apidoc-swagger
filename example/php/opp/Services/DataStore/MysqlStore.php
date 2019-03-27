<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/6/6
 * Time: 11:30 AM
 */

namespace App\Services\DataStore;

use Illuminate\Database\Connection;
use App\Services\Utils\BatchSqlAssembler;

class MysqlStore implements StoreInterface
{
    /**
     * @var Connection
     */
    protected $connector;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $maxLine = 1000;

    /**
     * MysqlStore constructor.
     * @param $connector
     * @param string $table
     */
    public function __construct($connector, string $table)
    {
        $this->connector = $connector;

        $this->table = $table;
    }

    /**
     * 将数据落入底表
     * @author: meyeryan@tencent.com
     *
     * @param array $data
     * @param bool $insertFlag
     * @return bool
     */
    public function save(array $data, $insertFlag = true)
    {
        if (\count($data) === 0) {
            return true;
        }

        $keys = array_keys(current($data));

        $orderDataWithoutKey = $this->adjustOrder($keys, $data);

        $start = 0;
        $total = \count($orderDataWithoutKey);
        while ($start < $total) {
            $this->connector->statement(BatchSqlAssembler::buildImplodeSQL(
                $keys,
                $this->table,
                \array_slice($orderDataWithoutKey, $start, $this->maxLine),
                $insertFlag
            ));
            $start += $this->maxLine;
        }

        return true;
    }

    /**
     *
     * 清理底表数据
     *
     * @return bool|void 是否执行成功
     */
    public function clean()
    {
        $this->connector->statement("truncate table {$this->table}");
    }

    /**
     * 按指定字段顺序, 返回数组值
     *
     * @param   array $fields 字典顺序
     * @param   array $data 原始数组
     *
     * @return array
     */
    protected function adjustOrder($fields, $data)
    {
        $result = [];
        foreach ($data as $datum) {
            $subResult = [];
            foreach ($fields as $field) {
                $subResult[] = $datum[$field] ?? '';
            }
            $result[] = $subResult;
        }

        return $result;
    }
}
