<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\ConstDef\OpportunityDef;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Hydrators\ForecastHydrator;

/**
 * This is the model class for table "t_opp_forecast".
 *
 * @property int id
 * @property string opportunity_id
 * @property string forecast_id
 * @property int year
 * @property int q
 * @property string forecast_money
 * @property string video_forecast_money
 * @property string news_forecast_money
 * @property string order_money
 * @property string video_order_money
 * @property string news_order_money
 * @property string forecast_money_remain
 * @property string video_forecast_money_remain
 * @property string news_forecast_money_remain
 * @property string order_rate 商机完成率
 * @property string video_order_rate 商机视频完成率
 * @property string news_order_rate 商机新闻完成率
 * @property string begin
 * @property string end
 * @property int Fis_del
 * @property string created_by
 * @property Carbon Fcreated_at
 * @property string updated_by
 * @property Carbon Fupdated_at
 */
class Forecast extends Model
{
    protected $maps = [
        'id'                           => 'Fid',
        'opportunity_id'               => 'Fopportunity_id',
        'forecast_id'                  => 'Fforecast_id',
        'year'                         => 'Fyear',
        'q'                            => 'Fq',
        'forecast_money'               => 'Fforecast_money',
        'forecast_money_remain'        => 'Fforecast_money_remain',
        'order_money'                  => 'Forder_money',
        'order_rate'                   => 'Forder_rate',
        'video_forecast_money'         => 'Fvideo_forecast_money',
        'video_order_money'            => 'Fvideo_order_money',
        'video_order_rate'             => 'Fvideo_order_rate',
        'video_forecast_money_remain'  => 'Fvideo_forecast_money_remain',
        'news_forecast_money'          => 'Fnews_forecast_money',
        'news_order_rate'              => 'Fnews_order_rate',
        'news_order_money'             => 'Fnews_order_money',
        'news_forecast_money_remain'   => 'Fnews_forecast_money_remain',
        'begin'                        => 'Fbegin',
        'end'                          => 'Fend',
        'is_del'                       => 'Fis_del',
        'created_by'                   => 'Fcreated_by',
        'created_at'                   => 'Fcreated_at',
        'updated_by'                   => 'Fupdated_by',
        'updated_at'                   => 'Fupdated_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('delete', function (Builder $builder) {
            $builder->where('Fis_del', OpportunityDef::NOT_DELETED);
        });
        self::creating(function (self $model) {
            $model->forecast_id = $model->forecast_id ?? (string) guid();
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
        $hydrator = new ForecastHydrator();
        return $hydrator->hydrate($attributes, $this);
    }

    /**
     * 根据 Forecast Id 查找代理商.
     *
     * @param $forecastId
     *
     * @return self
     */
    public static function findByForecastIdOrFail($forecastId)
    {
        return self::where('Fforecast_id', $forecastId)->firstOrFail();
    }

    /**
     * @param $opportunity
     *
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getForecasts(Opportunity $opportunity)
    {
        return self::query()
            ->where('Fopportunity_id', $opportunity->opportunity_id)
            ->get();
    }

    /**
     * 根据 forecastIds 查找.
     *
     * @param $forecastIds
     *
     * @return Collection
     */
    public static function findByForecastIds($forecastIds)
    {
        return self::whereIn('Fforecast_id', $forecastIds)->get();
    }

    /**
     * 按 ID 软删除.
     *
     * @param array $forecastIds
     */
    public static function softDeleteByIds($forecastIds)
    {
        return self::query()->whereIn('Fforecast_id', $forecastIds)
        ->update([
            OpportunityDef::FIELD_DELETE => OpportunityDef::DELETED,
        ]);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function details()
    {
        return $this->hasMany(Detail::class, 'Fforecast_id', 'Fforecast_id');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_opp_forecast';

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
