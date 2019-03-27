<?php

namespace App\Models;

use App\Services\ConstDef\OpportunityDef;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * This is the model class for table "t_opp_process".
 *
 * @property int $id
 * @property string $opportunity_id
 * @property string $opp_process_id 商机进程 id
 * @property int $step 进程阶段
 * @property int $probability 自评赢单概率
 * @property int $manager_probability 主管赢单概率
 * @property string $comment 进程说明
 * @property int $is_del
 * @property string $created_by
 * @property Carbon $created_at
 * @property string $updated_by
 * @property Carbon $updated_at
 */
class Process extends Model
{
    protected $maps = [
        'id'                           => 'Fid',
        'opportunity_id'               => 'Fopportunity_id',
        'opp_process_id'               => 'Fopp_process_id',
        'step'                         => 'Fstep',
        'probability'                  => 'Fprobability',
        'manager_probability'          => 'Fmanager_probability',
        'comment'                      => 'Fcomment',
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
            $builder->where('is_del', OpportunityDef::NOT_DELETED);
        });
        self::creating(function (self $model) {
            $model->opp_process_id = $model->opp_process_id ? $model->opp_process_id : (string)guid();
            $model->created_by = Auth::user()->getAuthIdentifier();
            $model->updated_by = Auth::user()->getAuthIdentifier();
            $model->created_at = time();
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
    protected $table = 't_opp_process';

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

    /**
     * 根据商机 ID 查找商机进程.
     *
     * @param $opportunityId
     * @return Builder
     */
    public static function findByOpportunityId($opportunityId)
    {
        return self::where('Fopportunity_id', $opportunityId);
    }

    /**
     * 通过商机来创建process
     * @param Opportunity $opportunity
     * @return Process
     */
    public static function buildByOpportunity(Opportunity $opportunity)
    {
        $model = new self();
        $model->opportunity_id = $opportunity->opportunity_id;
        $model->step = $opportunity->step;
        $model->probability = $opportunity->probability;
        $model->manager_probability = $opportunity->manager_probability;
        $model->comment = $opportunity->step_comment;
        return $model;
    }
}
