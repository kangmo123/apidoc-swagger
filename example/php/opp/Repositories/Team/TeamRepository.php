<?php
namespace App\Repositories\Team;

use App\Repositories\QuarterFilter;

interface TeamRepository
{
    public function getQuarterly(TeamFilter $filter, QuarterFilter $quarterFilter);
}
