<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    /**
     * 按IDS的查询顺序返回结果
     *
     * 这个函数是为了避免Model::find([3,2,1])结果出来的数据是[1,2,3]，顺序变掉了
     * 因为底层是用where id in (3,2,1)去查询的，结果出来排序可能是id默认排序的
     * @param $ids
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findWithOrder($ids, $columns = ['*'])
    {
        $finds = static::find($ids, $columns);
        if (!empty($finds) and $finds instanceof Collection) {
            $model = new static;
            $finds = $finds->keyBy($model->getKeyName());
            $collection = $model->newCollection();
            foreach ($ids as $id) {
                if (isset($finds[$id])) {
                    $collection->push($finds[$id]);
                }
            }
            return $collection;
        }
        return $finds;
    }
}
