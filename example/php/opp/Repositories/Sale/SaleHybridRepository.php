<?php
namespace App\Repositories\Sale;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class SaleHybridRepository extends ForecastArchHybridRepository implements SaleRepository
{
    public function getQuarterly(SaleFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
