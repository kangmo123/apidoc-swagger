<?php
namespace App\Repositories\Sale;

use App\Repositories\QuarterFilter;

interface SaleRepository
{
    public function getQuarterly(SaleFilter $filter, QuarterFilter $quarterFilter);
}
