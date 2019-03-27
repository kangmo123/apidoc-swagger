<?php

namespace App\Repositories\MerchantTags;

use App\Models\MerchantTags\MerchantTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MerchantTagDBRepository implements MerchantTagRepository
{
    /**
     * @param MerchantTagFilter $filter
     * @return LengthAwarePaginator
     */
    public function getPaginator(MerchantTagFilter $filter)
    {
        $query = MerchantTag::query();
        if ($filter->getTag()) {
            $query->where('tag', 'LIKE', "%{$filter->getTag()}%");
        }
        if ($filter->getMerchantCode()) {
            $query->whereRaw("FIND_IN_SET(?, merchant_code)", [$filter->getMerchantCode()]);
        }
        if ($filter->getPolicyGrade()) {
            $query->whereHas('policyGrades', function (Builder $query) use ($filter) {
                $query->where('policy_grade', '=', $filter->getPolicyGrade());
            });
        }
        if (in_array('policy_grades', $filter->getInclude())) {
            $query->with('policyGrades');
        }
        $query->orderByDesc('updated_at');
        return $query->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
