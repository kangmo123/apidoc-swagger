<?php
namespace App\Repositories\Project;

use App\Repositories\QuarterFilter;

interface ProjectRepository
{
    public function getQuarterly(ProjectFilter $filter, QuarterFilter $quarterFilter);
}
