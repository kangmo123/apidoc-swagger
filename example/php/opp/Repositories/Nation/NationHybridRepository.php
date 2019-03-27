<?php
namespace App\Repositories\Nation;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class NationHybridRepository extends ForecastArchHybridRepository implements NationRepository
{
    public function getQuarterly(NationFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
