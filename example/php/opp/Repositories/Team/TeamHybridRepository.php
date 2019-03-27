<?php
namespace App\Repositories\Team;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class TeamHybridRepository extends ForecastArchHybridRepository implements TeamRepository
{
    public function getQuarterly(TeamFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
