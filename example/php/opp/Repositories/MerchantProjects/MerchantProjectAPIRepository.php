<?php

namespace App\Repositories\MerchantProjects;

use App\Services\Workbench\WorkbenchService;
use Illuminate\Pagination\LengthAwarePaginator;

class MerchantProjectAPIRepository implements MerchantProjectRepository
{
    /**
     * @param MerchantProjectFilter $filter
     * @return LengthAwarePaginator
     */
    public function getPaginator(MerchantProjectFilter $filter)
    {
        $service = new WorkbenchService();
        $projectList = $service->getProjectList($filter);
        $total = intval($projectList['total'] ?? 0);
        $merchantProjects = $projectList['data'];
        $items = [];
        foreach ($merchantProjects as $merchantProject) {
            $items[] = MerchantProject::buildFromAPI($merchantProject);
        }
        return new LengthAwarePaginator($items, $total, $filter->getPerPage(), $filter->getPage());
    }
}
