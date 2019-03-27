<?php

namespace App\Entities;

use App\Models\Process;
use App\Models\Detail;
use App\Models\Forecast;

class OpportunityEnities extends Entity
{
    protected $fillable = ['opp_name', 'client_id', 'short_id', 'agent_id', 'brand_id', 'belong_to', 'is_share', 'owner_rtx', 'sale_rtx', 'channel_rtx', 'order_date', 'onboard_begin', 'onboard_end', 'forecast_money', 'forecast_money_remain', 'step'];

    /**
     * Get the morph class for this model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return 'App\\Opportunity';
    }

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'crm_brand';

    /**
     * Get all forecasts within this opportunity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function forecasts()
    {
        return $this->hasMany(Forecast::class);
    }

    /**
     * Get all details within this opportunity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function details()
    {
        return $this->hasMany(Detail::class);
    }

    /**
     * Get all details within this opportunity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        return $this->hasMany(Process::class, 'opportunity_id', 'opportunity_id');
    }
}
