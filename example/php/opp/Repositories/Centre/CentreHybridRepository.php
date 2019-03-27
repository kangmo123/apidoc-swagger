<?php
namespace App\Repositories\Centre;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class CentreHybridRepository extends ForecastArchHybridRepository implements CentreRepository
{
    public function getQuarterly(CentreFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
