<?php

namespace App\Models;

use Carbon\Carbon;
use App\Events\OppStepChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ConstDef\OpportunityDef;
use App\Http\Hydrators\OpportunityHydrator;

/**
 * This is the model class for table "t_opp".
 *
 * @property int $id ID 自增
 * @property string $opportunity_id 商机ID
 * @property string $opp_code 商机编码
 * @property int $data_from 商机数据来源：0-未知,1-crm_s,2-crm_ngs,3-win,4-twin;
 * @property string $opp_name 商机名称
 * @property string $client_id 直接客户ID
 * @property string $short_id 客户简称id
 * @property string $agent_id 策略代理ID
 * @property string $brand_id 品牌产品ID
 * @property int $belong_to 商机归属，1-销售，2-渠道
 * @property int $is_share 是否共享，0-否，1-是
 * @property string $owner_rtx 商机负责人
 * @property string $sale_rtx 销售rtx
 * @property string $channel_rtx 渠道rtx
 * @property string $order_date 预计签单时间
 * @property string $onboard_begin 投放开始时间
 * @property string $onboard_end 投放结束时间
 * @property float $forecast_money 预估投放总金额
 * @property float $forecast_money_remain 剩余预估金额
 * @property int $step 商机阶段，0-未知，2-跟进中，5-WIP，6-赢单，7-失单
 * @property int $state 商机状态，1-进行中，2-已关闭
 * @property int $probability 赢单概率
 * @property int $manager_probability 主管确认赢单概率
 * @property string $step_comment 商机阶段说明
 * @property int $risk_type 商机风险类型，0-未知，1-暂无风险，2-库存问题，3-资源问题，4-预算问题，5-价格问题，6-其他
 * @property string $risk_comment 商机风险说明
 * @property int $opp_type 商机类型，1-普通商机，2-智赢销商机，3-汇赢商机
 * @property int $status 商机状态
 * @property int $is_crucial 是否攻坚团队，0-否，1-是
 * @property string $crucial_rtx 攻坚团队人员，Fis_cucial=1事必填
 * @property int $opp_resource 商机来源，0-未知，1-渠道，2-直客，3-公司内部，4-其他，5-攻坚团队
 * @property int $frame_type 框架类型，0-未知，1-直客框架，2-代理框架，3-无框架，4-未定
 * @property int $help_type 所需支持类型，0-未知，1-代理支持(关系维护、政策支持、战略合作)，2-客户支持(关系维护、销售政策)，3-市场支持(会议营销、行业地位)，4-策划支持(策略倾向)，5-产品支持(特殊产品、频道内容支持)
 * @property string $help_comment 所需支持说明
 * @property string $close_date 商机关闭时间
 * @property float $close_value 商机关闭实际收入
 * @property string $close_comment 商机关闭备注说明
 * @property float $order_money 商机订单金额
 * @property float $order_rate 商机完成率
 * @property float $order_rate_real_time 商机实时完成率
 * @property float $video_forecast_money 商机视频预估金额
 * @property float $video_order_money 商机视频下单金额
 * @property float $video_order_rate 商机视频完成率
 * @property float $video_forecast_money_remain 商机视频剩余金额
 * @property float $news_forecast_money 商机新闻预估金额
 * @property float $news_order_money 商机新闻下单金额
 * @property float $news_order_rate 商机新闻完成率
 * @property float $news_forecast_money_remain 商机新闻剩余金额
 * @property int $is_del 是否逻辑删除：0-未删除，1-已删除
 * @property string $created_by 创建人RTX
 * @property Carbon $created_at 创建时间
 * @property string $updated_by 修改人RTX
 * @property Carbon $updated_at 修改时间
 *
 * @method static Builder expired()
 */
class Opportunity extends Model
{
    protected $primaryKey = 'Fid';

    const V2_LAST_OPP_ID = 165376; // 商机2.0上线前最新一条商机的自增 ID

    protected $maps = [
        'id'                         => 'Fid',
        'opportunity_id'             => 'Fopportunity_id',
        'opp_code'                   => 'Fopp_code',
        'data_from'                  => 'Fdata_from',
        'opp_name'                   => 'Fopp_name',
        'client_id'                  => 'Fclient_id',
        'short_id'                   => 'Fshort_id',
        'agent_id'                   => 'Fagent_id',
        'brand_id'                   => 'Fbrand_id',
        'belong_to'                  => 'Fbelong_to',
        'is_share'                   => 'Fis_share',
        'owner_rtx'                  => 'Fowner_rtx',
        'sale_rtx'                   => 'Fsale_rtx',
        'channel_rtx'                => 'Fchannel_rtx',
        'order_date'                 => 'Forder_date',
        'onboard_begin'              => 'Fonboard_begin',
        'onboard_end'                => 'Fonboard_end',
        'forecast_money'             => 'Fforecast_money',
        'forecast_money_remain'      => 'Fforecast_money_remain',
        'step'                       => 'Fstep',
        'state'                      => 'Fstate',
        'probability'                => 'Fprobability',
        'manager_probability'        => 'Fmanager_probability',
        'step_comment'               => 'Fstep_comment',
        'risk_type'                  => 'Frisk_type',
        'risk_comment'               => 'Frisk_comment',
        'opp_type'                   => 'Fopp_type',
        'status'                     => 'Fstatus',
        'is_crucial'                 => 'Fis_crucial',
        'crucial_rtx'                => 'Fcrucial_rtx',
        'opp_resource'               => 'Fopp_resource',
        'frame_type'                 => 'Fframe_type',
        'help_type'                  => 'Fhelp_type',
        'help_comment'               => 'Fhelp_comment',
        'close_date'                 => 'Fclose_date',
        'close_value'                => 'Fclose_value',
        'close_comment'              => 'Fclose_comment',
        'order_money'                => 'Forder_money',
        'order_rate'                 => 'Forder_rate',
        'order_rate_real_time'       => 'Forder_rate_real_time',
        'video_forecast_money'       => 'Fvideo_forecast_money',
        'video_order_money'          => 'Fvideo_order_money',
        'video_order_rate'           => 'Fvideo_order_rate',
        'video_forecast_money_remain'=> 'Fvideo_forecast_money_remain',
        'news_forecast_money'        => 'Fnews_forecast_money',
        'news_order_rate'            => 'Fnews_order_rate',
        'news_order_money'           => 'Fnews_order_money',
        'news_forecast_money_remain' => 'Fnews_forecast_money_remain',
        'is_del'                     => 'Fis_del',
        'created_by'                 => 'Fcreated_by',
        'created_at'                 => 'Fcreated_at',
        'updated_by'                 => 'Fupdated_by',
        'updated_at'                 => 'Fupdated_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('delete', function (Builder $builder) {
            $builder->where('Fis_del', OpportunityDef::NOT_DELETED);
        });
        self::creating(function (self $model) {
            $model->opp_name = filter_str($model->opp_name);
            $model->opportunity_id = $model->opportunity_id ? $model->opportunity_id : (string) guid();
            $model->opp_code = $model->opp_code ? $model->opp_code : (string) code();
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
     * 根据 opportunityId 查找.
     *
     * @param $opportunityId
     *
     * @return self
     */
    public static function findByOpportunityIdOrFail($opportunityId)
    {
        return self::where('Fopportunity_id', $opportunityId)->firstOrFail();
    }

    /**
     * 根据 opportunityIds 查找.
     *
     * @param $opportunityIds
     *
     * @return Collection
     */
    public static function findByOpportunityIds($opportunityIds)
    {
        return self::whereIn('Fopportunity_id', $opportunityIds)->get();
    }

    /**
     * 根据 oppName 查找代理商.
     *
     * @param $oppName
     *
     * @return self
     */
    public static function findByOppNameOrFail($oppName)
    {
        return self::where('Fopp_name', $oppName)->firstOrFail();
    }

    /**
     * 根据 oppName 查找代理商.
     *
     * @param $oppName
     * @param $opportunityId
     *
     * @return self
     */
    public static function nameExist($oppName, $opportunityId = null)
    {
        return $opportunity = self::where(
            [
                ['Fopportunity_id', '!=', $opportunityId],
                ['Fopp_name', '=', $oppName],
            ]
        )->exists();
    }

    /**
     * @param $attributes
     *
     * @return Opportunity
     */
    public function insertRow($attributes)
    {
        $hydrator = new OpportunityHydrator();
        $model = new Opportunity();
        return $hydrator->hydrate($attributes, $model);
    }

    /**
     * @param $attributes
     * @param Opportunity $models
     *
     * @return Opportunity
     */
    public function updateRow($attributes, $model)
    {
        $hydrator = new OpportunityHydrator();
        return $hydrator->hydrate($attributes, $model);
    }

    /**
     * @var Forecasts
     */
    protected $forecasts;

    const CREATED_AT = 'Fcreated_at';

    const UPDATED_AT = 'Fupdated_at';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function forecasts()
    {
        return $this->hasMany(Forecast::class, 'Fopportunity_id', 'Fopportunity_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        return $this->hasMany(Process::class, 'Fopportunity_id', 'Fopportunity_id');
    }

    /**
     * 设置相关 Forecast 模型.
     *
     * @param Forecast $forecasts
     *
     * @return $this
     */
    public function setForecasts(Forecast $forecasts)
    {
        $this->forecasts = $forecasts;
        return $this;
    }

    /**
     * 获取相关 Forecast 模型.
     *
     * @return Forecast
     */
    public function getForecasts()
    {
        if ($this->forecasts) {
            return $this->forecasts;
        }
        if (isset($this->relations['forecasts'])) {
            return $this->forecasts;
        }
        $this->forecasts = $this->forecasts();
        return $this->forecasts;
    }

    /**
     * 过期的商机
     * @param Builder $query
     * @return Builder
     */
    public function scopeExpired(Builder $query)
    {
        $query = $query->where('id', '>', self::V2_LAST_OPP_ID);
        $query = $query->whereIn('step', [OpportunityDef::STEP_ON_GOING, OpportunityDef::STEP_WIP]);
        $query = $query->where('onboard_end', '<', date('Y-m-d'));
        return $query;
    }

    /**
     * 修改商机状态
     * @param $step
     * @param $comment
     * @return bool
     */
    public function changeStepTo($step, $comment = '')
    {
        $fromStep = $this->step;
        $toStep = $step;
        $this->step = $step;
        $this->step_comment = $comment;
        $this->probability = $this->getProbabilityByStep($step);
        event(new OppStepChanged($this, $fromStep, $toStep, $comment));
        if ($step == OpportunityDef::STEP_LOSE) {
            $this->state = OpportunityDef::STATE_CLOSED;
            $this->close_comment = $comment;
        }
        return $this->save();
    }

    /**
     * 通过商机阶段获得自评赢单概率
     * @param $step
     * @return int
     */
    public function getProbabilityByStep($step)
    {
        return OpportunityDef::STEP_PROBABILITY[$step] ?? 0;
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_opp';

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
