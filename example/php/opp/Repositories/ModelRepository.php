<?php

namespace App\Repositories;

/**
 * Interface ModelRepository
 * @package App\Repositories
 * @author caseycheng <caseycheng@tencent.com>
 */
interface ModelRepository
{
    public function getCount($criteria);

    public function getOneModel($criteria);

    public function getModels($criteria, $limit, $offset);
}
