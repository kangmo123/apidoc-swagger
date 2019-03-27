<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/7/15
 * Time: 2:16 PM
 */

namespace App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use App\Services\ConstDef\OpportunityDef;

abstract class ForecastFilter
{
    protected $saleType;

    protected $limit;

    protected $offset;

    protected $sort;

    protected $groupBy;

    /**
     * ForecastFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->saleType          = ((string) $request->get('channel_type', 'direct') == 'direct') ? OpportunityDef::BELONG_TO_SALE : OpportunityDef::BELONG_TO_CHANNEL;
        $this->limit             = (int) $request->get('per_page', 50);
        $this->offset            = ((int) $request->get('page', 1) - 1) * $this->limit;
        $this->sort              = parseSortString($request->get('sort', $this->defaultSort()));
        $this->groupBy           = parseCommaString($request->get('group_by', $this->defaultGroupBy()));
        $this->resolveFromRequest($request);
    }

    /**
     * 解析其他筛选条件
     * @param Request $request
     * @return mixed
     */
    abstract public function resolveFromRequest(Request $request);

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    abstract public function defaultSort();

    /**
     * 设置默认聚合规则
     * @return string
     */
    abstract public function defaultGroupBy();

    /**
     * @return mixed
     */
    public function getSaleType()
    {
        return $this->saleType;
    }

    /**
     * @param mixed $saleType
     */
    public function setSaleType($saleType): void
    {
        $this->saleType = $saleType;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param mixed $offset
     */
    public function setOffset($offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param mixed $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return array
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param $groupBy
     */
    public function setGroupBy($groupBy)
    {
        $this->groupBy = $groupBy;
    }

    /**
     * 额外过滤条件
     * @param Builder $query
     * @return Builder
     */
    public function additionalQuery(Builder $query)
    {
        return $query;
    }
}
