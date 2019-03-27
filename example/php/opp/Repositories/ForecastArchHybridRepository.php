<?php
namespace App\Repositories;

class ForecastArchHybridRepository
{
    protected $databaseRepository;

    public function getDatabaseRepository(): ForecastDatabaseRepository
    {
        if (!$this->databaseRepository) {
            $this->databaseRepository = new ForecastDatabaseRepository();
        }
        return $this->databaseRepository;
    }

    /**
     * 根据常规查询条件,时间查询条件以及聚合字段,从数据源获得数据
     * @param $filter
     * @param QuarterFilter|DateFilter $timeFilter
     * @param $tableName
     * @return array
     * @throws \ReflectionException
     */
    public function fetch($filter, $timeFilter, $tableName)
    {
        return $this->getDatabaseRepository()->fetch($filter, $timeFilter, $tableName);
    }


    /**
     * @param $data
     * @param $total
     * @param ForecastFilter $filter
     * @return array
     */
    public function fillPageInfo($data, $total, $filter): array
    {
        $limit = (int)$filter->getLimit();
        return [
            'data' => $data,
            'page_info' => [
                'page' => $limit > 0 ? floor($filter->getOffset() / $limit) + 1 : 1,
                'per_page' => $limit,
                'total_page' => $limit > 0 ? ceil($total / $limit) : 1,
                'total_number' => $total
            ]
        ];
    }
}
