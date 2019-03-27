<?php

namespace App\Repositories;

use App\Models\Process;

class ProcessDatabaseRepository implements ModelRepository
{
    /**
     * @param $criteria
     *
     * @return int
     */
    public function getCount($criteria)
    {
        $query = Process::query();
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
        $query = Process::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        $process = $query->first();
        return $process;
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
        $query = Process::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        if (isset($limit)) {
            $query->limit($limit);
        }
        if (isset($offset)) {
            $query->offset($offset);
        }
        $items = $query->get();
        return $items;
    }

    /**
     *
     * @param ProcessFilter $filter
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginator(ProcessFilter $filter)
    {
        $query = Process::query();
        if ($filter->getOpportunityId()) {
            $query->where('Fopportunity_id', $filter->getOpportunityId());
        }
        if ($sort = $filter->getSort()) {
            foreach ($sort as $key => $value) {
                $query->orderBy($key, $value);
            }
        }
        return $query->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }
}
