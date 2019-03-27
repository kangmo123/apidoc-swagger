<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\ConstDef\OpportunityDef;
use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "t_opp_options_relation".
 *
 * @property integer id
 * @property integer platform 投放平台
 * @property string cooperation 合作形式
 * @property string project 招商项目
 * @property string product 广告产品
 * @property string form 播放形式
 * @property string seat 席位名
 * @property integer is_del
 * @property string created_by
 * @property string created_at
 * @property string updated_by
 * @property string updated_at
 */
class OptionsRelation extends Model
{
    protected $maps = [
        'id'                => 'Fid',
        'platform'          => 'Fplatform',
        'cooperation'       => 'Fcooperation',
        'project'           => 'Fproject',
        'product'           => 'Fproduct',
        'form'              => 'Fform',
        'seat'              => 'Fseat',
        'is_del'            => 'Fis_del',
        'created_by'        => 'Fcreated_by',
        'created_at'        => 'Fcreated_at',
        'updated_by'        => 'Fupdated_by',
        'updated_at'        => 'Fupdated_at',
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
            $model->is_del = OpportunityDef::NOT_DELETED;
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
    protected $table = 't_opp_options_relation';

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
