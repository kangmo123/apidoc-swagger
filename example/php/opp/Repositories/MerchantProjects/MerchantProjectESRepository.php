<?php

namespace App\Repositories\MerchantProjects;

use Illuminate\Pagination\LengthAwarePaginator;

class MerchantProjectESRepository implements MerchantProjectRepository
{
    /**
     * @param MerchantProjectFilter $filter
     * @return LengthAwarePaginator
     */
    public function getPaginator(MerchantProjectFilter $filter)
    {
        // TODO: 从ES里面做查询
        return new LengthAwarePaginator([], 0, 10, 1);
    }
}
