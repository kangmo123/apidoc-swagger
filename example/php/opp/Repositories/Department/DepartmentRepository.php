<?php
namespace App\Repositories\Department;

use App\Repositories\QuarterFilter;

interface DepartmentRepository
{
    public function getQuarterly(DepartmentFilter $filter, QuarterFilter $quarterFilter);
}
