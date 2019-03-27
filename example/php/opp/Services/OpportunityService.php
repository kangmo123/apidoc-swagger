<?php

namespace App\Services;

use App\Models\Opportunity;

class OpportunityService
{
    /**
     * 获取过期的商机
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getExpired()
    {
        return Opportunity::expired()->get();
    }
}
