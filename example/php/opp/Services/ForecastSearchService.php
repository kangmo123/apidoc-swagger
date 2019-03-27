<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ConstDef\OpportunityDef;
use Illuminate\Http\Request;
use App\Models\Forecast;

/**
 * @author hubertchen <hubertchen@tencent.com>
 */
class ForecastSearchService
{
    /**
     * 整理好的查询参数.
     *
     * @var array
     */
    private $params = [];

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    private $query;

    /**
     * paging setting.
     *
     * @var array
     */
    private $paging = [];

    protected $operator = [];

    /**
     * 可查询的字段属性.
     *
     * fuzzyMatch 模糊搜索
     * whereIn 验证字段的值必须存在指定的数组里.
     */
    protected $allowedFields = [
        'opportunity_id' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'forecast_id' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'year' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'q' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'forecast_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'forecast_money_remain' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'order_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'order_rate' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'order_rate_real_time' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'video_forecast_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'video_order_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'video_order_rate' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'video_forecast_money_remain' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'news_forecast_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'news_order_rate' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'news_order_money' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'news_forecast_money_remain' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'created_by' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'created_at' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'updated_by' => [
            'fuzzyMatch'   => false,
            'whereIn'      => true,
        ],
        'updated_at' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'begin' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
        'end' => [
            'fuzzyMatch'   => false,
            'whereIn'      => false,
        ],
    ];

    public function __construct(Request $request)
    {
        $this->params = $this->initParams($request);
        Log::info('params:' . var_export($this->params, 1));

        $this->fields = $this->initFields($request);
        $this->query  = Forecast::query();

        $this->buildRequestQuery();

        if ($request->input('toSql')) {
            $this->buildPagingQuery();
            dd($this->query->toSql());
        }
    }

    /**
     * 获取计数.
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * 只获取 ID 数组.
     */
    public function getIds()
    {
        $this->buildPagingQuery();
        return $this->query->get()->pluck('Fforecast_id')->toArray();
    }

    /**
     * 准备查询参数.
     *
     * @param Request $request
     *
     * @return array
     */
    private function initParams($request)
    {
        // 调用方指定操作符数组
        $this->operator = $request->input('operator');

        // 补全分页参数默认值
        $this->paging['page']   = $request->input('page', OpportunityDef::DEFAULT_PAGE);
        $this->paging['perPage']= $request->input('per_page', OpportunityDef::DEFAULT_PER_PAGE);

        // 保留白名单中有的查询参数
        $params = array_intersect_key($request->all(), $this->allowedFields);
        // 删除空值参数 不包括 0
        return array_filter($params);
    }

    /**
     * 准备查询结果字段.
     *
     * @param Request $request
     *
     * @return array
     */
    private function initFields($request)
    {
        return $request->input('fields', 'Fforecast_id');
    }

    /**
     * 根据请求的参数和可以查询的参数，构造Query.
     */
    protected function buildRequestQuery()
    {
        array_walk($this->params, function ($value, $field, $fieldSettings) {
            $fieldSetting = $fieldSettings[$field];

            $field = $fieldSetting['field'] ?? $field;
            $value = $fieldSetting['whereIn'] ? explode(',', $value) : $value;
            $operator = $fieldSetting['fuzzyMatch'] ? 'like' : '=';
            $this->buildFieldQuery($field, $value, $operator);
        }, $this->allowedFields);
    }

    private function buildPagingQuery()
    {
        $this->query->paginate($this->paging['perPage'], ['*'], 'page', $this->paging['page']);
    }

    /**
     * @param string       $field
     * @param string|array $value
     * @param string       $operator
     */
    private function buildFieldQuery($field, $value, $operator)
    {
        if (is_array($value)) {
            $this->query->whereIn($field, $value);
        } else {
            $operator = $this->operator[$field] ?? $operator;
            $this->query->where($field, $operator, $value);
        }
    }
}
