<?php
namespace App\Repositories\Area;

use App\Repositories\QuarterFilter;

interface AreaRepository
{
    public function getQuarterly(AreaFilter $filter, QuarterFilter $quarterFilter);
}
