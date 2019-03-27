<?php
namespace App\Repositories\Centre;

use App\Repositories\QuarterFilter;

interface CentreRepository
{
    public function getQuarterly(CentreFilter $filter, QuarterFilter $quarterFilter);
}
