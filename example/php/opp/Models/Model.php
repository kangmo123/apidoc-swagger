<?php

namespace App\Models;

use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 * Class Model.
 *
 * @author caseycheng <caseycheng@tencent.com>
 */
abstract class Model extends BaseModel
{
    use Eloquence, Mappable;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['Fcreated_at', 'Fupdated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if ($maps = $this->getMaps()) {
            $this->append(array_keys($maps));
            $this->setHidden(array_values($maps));

            if (!$this->fillable) {
                $this->fillable = array_keys($maps);
            }
        }
    }

    /**
     * 按IDS的查询顺序返回结果.
     *
     * 这个函数是为了避免Model::find([3,2,1])结果出来的数据是[1,2,3]，顺序变掉了
     * 因为底层是用where id in (3,2,1)去查询的，结果出来排序可能是id默认排序的
     *
     * @param $ids
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findWithOrder($ids, $columns = ['*'])
    {
        $finds = static::find($ids, $columns);
        if (!empty($finds) and $finds instanceof Collection) {
            $model      = new static;
            $finds      = $finds->keyBy($model->getKeyName());
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

    /**
     * 返回带库名前缀的表名.
     *
     * @return string
     */
    public function tableName()
    {
        $dbName = '';
        if ($this->connection == 'crm_kernel') {
            $dbName = 'crm_kernal';
        }
        if (!empty($dbName)) {
            return $dbName . '.' . $this->getTable();
        } else {
            return $this->getTable();
        }
    }
}
