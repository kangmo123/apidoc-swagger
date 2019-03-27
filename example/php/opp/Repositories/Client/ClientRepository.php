<?php
namespace App\Repositories\Client;

use App\Repositories\QuarterFilter;

interface ClientRepository
{
    public function getQuarterly(ClientFilter $filter, QuarterFilter $quarterFilter);
}
