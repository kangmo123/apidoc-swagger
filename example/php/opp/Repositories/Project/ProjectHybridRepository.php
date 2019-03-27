<?php
namespace App\Repositories\Project;

use App\Repositories\QuarterFilter;
use App\Repositories\ForecastRepository;
use App\Repositories\ForecastArchHybridRepository;

class ProjectHybridRepository extends ForecastArchHybridRepository implements ProjectRepository
{
    public function getQuarterly(ProjectFilter $filter, QuarterFilter $quarterFilter)
    {
        return $this->fetch($filter, $quarterFilter, ForecastRepository::FORECAST_DETAIL_ARCH_TABLE);
    }
}
