<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/7/17
 * Time: 3:00 PM
 */

namespace App\Repositories;

interface ForecastRepository
{
    const FORECAST_ARCH_TABLE = 't_opp_forecast_owner_arch';

    const FORECAST_DETAIL_ARCH_TABLE = 't_opp_forecast_detail_owner_arch';

    /**
     * @param $filter
     * @param QuarterFilter|DateFilter $timeFilter
     * @param $tableName
     * @return mixed
     */
    public function fetch($filter, $timeFilter, $tableName);

}
