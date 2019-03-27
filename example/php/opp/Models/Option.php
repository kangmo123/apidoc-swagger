<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\ConstDef\OpportunityDef;

/**
 * This is the model class for table "t_opp_options".
 *
 * @property integer id
 * @property integer type
 * @property string key
 * @property string value
 * @property integer parent
 * @property integer is_del
 * @property string created_by
 * @property string created_at
 * @property string updated_by
 * @property string updated_at
 */
class Option extends Model
{
    protected $maps = [
        'id'         => 'Fid',
        'type'       => 'Ftype',
        'key'        => 'Fkey',
        'value'      => 'Fvalue',
        'parent'     => 'Fparent',
        'is_del'     => 'Fis_del',
        'created_by' => 'Fcreated_by',
        'created_at' => 'Fcreated_at',
        'updated_by' => 'Fupdated_by',
        'updated_at' => 'Fupdated_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('delete', function (Builder $builder) {
            $builder->where('is_del', OpportunityDef::NOT_DELETED);
        });
        self::creating(function (self $model) {
            $model->created_by = Auth::user()->getAuthIdentifier();
            $model->updated_by = Auth::user()->getAuthIdentifier();
            $model->is_del = self::IS_NOT_DEL;
        });
        self::updating(function (self $model) {
            $model->updated_by = Auth::user()->getAuthIdentifier();
        });
    }

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_opp_options';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'crm_brand';
}
