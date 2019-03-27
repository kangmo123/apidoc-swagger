<?php

namespace App\Repositories;

use App\Models\Opportunity;

class OpportunityDatabaseRepository implements ModelRepository
{
    /**
     * @param $criteria
     *
     * @return int
     */
    public function getCount($criteria)
    {
        $query = Opportunity::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        $count = $query->count();
        return $count;
    }

    /**
     *
     * @param $criteria
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function getOneModel($criteria)
    {
        $query = Opportunity::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        $opportunity = $query->first();
        return $opportunity;
    }

    /**
     *
     * @param $criteria
     * @param $limit
     * @param $offset
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getModels($criteria, $limit, $offset)
    {
        $query = Opportunity::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        if (isset($limit)) {
            $query->limit($limit);
        }
        if (isset($offset)) {
            $query->offset($offset);
        }
        $opportunities = $query->get();
        return $opportunities;
    }

    /**
     *
     * @param OpportunityFilter $filter
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginator(OpportunityFilter $filter)
    {
        $query = Opportunity::query();
        if ($filter->getOpportunityId()) {
            $query->where('opportunity_id', $filter->getOpportunityId());
        }
        if ($filter->getOppName()) {
            $query->where('opp_name', $filter->getOppName());
        }
        if ($filter->getKeyword()) {
            $query->where('opp_name', 'LIKE', "%{$filter->getKeyword()}%");
        }
        if ($sort = $filter->getSort()) {
            foreach ($sort as $key => $value) {
                $query->orderBy($key, $value);
            }
        }
        return $query->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
