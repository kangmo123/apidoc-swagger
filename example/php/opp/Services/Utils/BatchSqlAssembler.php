<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/6/6
 * Time: 11:46 AM
 */

namespace App\Services\Utils;

class BatchSqlAssembler
{
    public static function buildImplodeSQL($fields, $table, $data, $insertFlag = true)
    {
        if ($insertFlag) {
            $sqlPre = "INSERT INTO {$table}" . " (" . implode(',', $fields) . ") VALUES ";
        } else {
            $sqlPre = "REPLACE INTO {$table}" . " (" . implode(',', $fields) . ") VALUES ";
        }

        $retArr = array();
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $retArr[] = "('" . implode("','", array_map(function ($item) {
                    return htmlspecialchars($item, ENT_QUOTES);
                }, $value)) . "')";
            } else {
                $retArr[] = "'" . $value . "'";
            }
        }
        $ret = $sqlPre . implode(',', $retArr);

        return $ret;
    }
}
