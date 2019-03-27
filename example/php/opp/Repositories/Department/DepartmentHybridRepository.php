<?php
namespace App\Repositories\Department;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class DepartmentHybridRepository extends ForecastArchHybridRepository implements DepartmentRepository
{
    public function getQuarterly(DepartmentFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_ARCH_TABLE);
    }
}
