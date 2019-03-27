<?php

namespace App\Repositories\MerchantProjects;

use Illuminate\Pagination\LengthAwarePaginator;

interface MerchantProjectRepository
{
    /**
     * @param MerchantProjectFilter $filter
     * @return LengthAwarePaginator
     */
    public function getPaginator(MerchantProjectFilter $filter);
}
