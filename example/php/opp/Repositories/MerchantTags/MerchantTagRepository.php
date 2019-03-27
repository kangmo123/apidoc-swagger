<?php

namespace App\Repositories\MerchantTags;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MerchantTagRepository
{
    /**
     * @param MerchantTagFilter $filter
     * @return LengthAwarePaginator
     */
    public function getPaginator(MerchantTagFilter $filter);
}
