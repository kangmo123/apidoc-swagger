<?php
namespace App\Repositories;

use ReflectionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class ForecastDatabaseRepository implements ForecastRepository
{
    protected $forecastFactory;

    protected $connection;

    protected $sumFields = [
        'forecast_money',
        'wip_money',
        'ongoing_money',
        'order_money',
        'remain_money',
    ];

    protected $fieldsRename = [
        'product_type'   => 'product',
        'forecast_money' => 'opp_q_forecast',
        'wip_money'      => 'opp_q_wip',
        'ongoing_money'  => 'opp_q_ongoing',
        'order_money'    => 'opp_q_order',
        'remain_money'   => 'opp_q_remain',
    ];

    /**
     * @param Builder $query
     * @param ForecastFilter $filter
     * @return Builder
     * @throws \ReflectionException
     */
    protected function buildQuery($query, $filter)
    {
        $statisticsSum = [];
        foreach ($this->sumFields as $field) {
            $newFiled = isset($this->fieldsRename[$field]) ? $this->fieldsRename[$field] : $field;
            $statisticsSum[] = "sum({$field}) as {$newFiled}";
        }

        $query->select(DB::raw(implode(', ', array_merge($filter->getGroupBy(), $statisticsSum))));

        $getMethodArr = [
            'getLimit',
            'getOffset',
            'getSort',
            'getGroupBy',
        ];

        $ref = new ReflectionClass($filter->className());
        $methods = $ref->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'get') === 0 && !\in_array($methodName, $getMethodArr, false)) {
                $field = snake_case(substr($methodName, 3));
                $value = \call_user_func_array([$filter, $methodName], []);

                if ($value && \is_array($value)) {
                    $query->whereIn($field, $value);
                    continue;
                }
                if ($value && !\is_array($value)) {
                    $query->where($field, $value);
                    continue;
                }
            }
        }

        //额外过滤条件
        if (method_exists($filter, 'additionalQuery')) {
            $query = $filter->additionalQuery($query);
        }

        //聚合
        if (!empty($filter->getGroupBy())) {
            $query->groupBy($filter->getGroupBy());
        }

        //排序
        if (!empty($filter->getSort())) {
            $sort = $filter->getSort();
            foreach ($sort as $key => $order) {
                $query->orderBy($key, $order);
            }
        }

        //分页
        if ((int)$filter->getLimit() > 0) {
            $query->offset($filter->getOffset());
            $query->limit($filter->getLimit());
        }

        return $query;
    }

    /**
     * 根据常规查询条件,时间查询条件以及聚合字段,从数据源获得数据
     * @param ForecastFilter $filter
     * @param QuarterFilter|DateFilter $timeFilter
     * @param string $tableName
     * @return array
     * @throws \ReflectionException
     */
    public function fetch($filter, $timeFilter, $tableName)
    {
        $query = $this->getConnection($tableName);

        if ($year = $timeFilter->getYear()) {
            $query->where('year', $year);
        }
        if ($quarter = $timeFilter->getQuarter()) {
            $query->where('quarter', $quarter);
        }

        $query = $this->buildQuery($query, $filter);

        $data = $query->get();

        $result = [];
        foreach ($data as $item) {
            foreach ($this->fieldsRename as $oldName => $newName) {
                if (isset($item->{$oldName})) {
                    $item->{$newName} = $item->{$oldName};
                    unset($item->{$oldName});
                }
            }
            $result[] = (array) $item;
        }
        return $result;
    }

    /**
     * @param string $table
     * @return Builder
     */
    protected function getConnection($table)
    {
        return DB::connection('opportunity')->table($table);
    }
}
