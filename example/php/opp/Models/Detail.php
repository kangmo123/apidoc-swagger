<?php

namespace App\Models;

use App\Services\ConstDef\OpportunityDef;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Http\Hydrators\DetailHydrator;

/**
 * This is the model class for table "t_opp_forecast_detail".
 *
 * @property int id
 * @property string opportunity_id
 * @property string forecast_id
 * @property string forecast_detail_id
 * @property int year
 * @property int q
 * @property int platform
 * @property string cooperation_type
 * @property int business_project_id
 * @property string business_project
 * @property int ad_product_id
 * @property string ad_product
 * @property int resource_id
 * @property string resource_name
 * @property string other_resource
 * @property int forecast_money
 * @property string play_type
 * @property int play_type_id
 * @property int is_del
 * @property string created_by
 * @property Carbon Fcreated_at
 * @property string updated_by
 * @property Carbon Fupdated_at
 */
class Detail extends Model
{
    protected $maps = [
        'id'                 => 'Fid',
        'opportunity_id'     => 'Fopportunity_id',
        'forecast_id'        => 'Fforecast_id',
        'forecast_detail_id' => 'Fforecast_detail_id',
        'year'               => 'Fyear',
        'q'                  => 'Fq',
        'forecast_money'     => 'Fforecast_money',
        'platform'           => 'Fplatform',
        'cooperation_type'   => 'Fcooperation_type',
        'business_project_id'=> 'Fbusiness_project_id',
        'business_project'   => 'Fbusiness_project',
        'ad_product_id'      => 'Fad_product_id',
        'ad_product'         => 'Fad_product',
        'resource_id'        => 'Fresource_id',
        'resource_name'      => 'Fresource_name',
        'other_resource'     => 'Fother_resource',
        'play_type'          => 'Fplay_type',
        'play_type_id'       => 'Fplay_type_id',
        'is_del'             => 'Fis_del',
        'created_by'         => 'Fcreated_by',
        'created_at'         => 'Fcreated_at',
        'updated_by'         => 'Fupdated_by',
        'updated_at'         => 'Fupdated_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('delete', function (Builder $builder) {
            $builder->where('is_del', OpportunityDef::NOT_DELETED);
        });
        self::creating(function (self $model) {
            $model->forecast_detail_id = $model->forecast_detail_id ?? (string) guid();
            $model->created_by = Auth::user()->getAuthIdentifier();
            $model->updated_by = Auth::user()->getAuthIdentifier();
            $model->created_at = time();
            $model->is_del = OpportunityDef::NOT_DELETED;
        });
        self::updating(function (self $model) {
            $model->updated_by = Auth::user()->getAuthIdentifier();
        });
    }

    /**
     * @param $attributes
     *
     * @return Opportunity
     */
    public function insertRow($attributes)
    {
        $hydrator = new DetailHydrator();
        return $hydrator->hydrate($attributes, $this);
    }

    /**
     * 按 forecast ID 软删除预估详情.
     *
     * @param array $forecastIds
     */
    public static function softDeleteByIds($forecastIds)
    {
        return self::query()
        ->whereIn('Fforecast_id', $forecastIds)
        ->update(
            [OpportunityDef::FIELD_DELETE=> OpportunityDef::DELETED]
        );
    }

    const CREATED_AT = 'Fcreated_at';

    const UPDATED_AT = 'Fupdated_at';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function opportunity()
    {
        return $this->hasOne(Opportunity::class, 'Fopportunity_id', 'Fopportunity_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function forecast()
    {
        return $this->hasOne(Forecast::class, 'Fforecast_id', 'Fforecast_id');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_opp_forecast_detail';

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
