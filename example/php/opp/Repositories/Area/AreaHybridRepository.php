<?php
namespace App\Repositories\Area;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class AreaHybridRepository extends ForecastArchHybridRepository implements AreaRepository
{
    public function getQuarterly(AreaFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
