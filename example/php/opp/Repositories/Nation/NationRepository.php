<?php
namespace App\Repositories\Nation;

use App\Repositories\QuarterFilter;

interface NationRepository
{
    public function getQuarterly(NationFilter $filter, QuarterFilter $quarterFilter);
}
