<?php
namespace App\Repositories\Client;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class ClientHybridRepository extends ForecastArchHybridRepository implements ClientRepository
{
    public function getQuarterly(ClientFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
