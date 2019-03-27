<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ConstDef\OpportunityDef;
use App\Models\Opportunity;
use Illuminate\Http\Request;

/**
 * @author hubertchen <hubertchen@tencent.com>
 */
class OpportunitySearchService
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
     * whereType 验证字段的值必须存在指定的数组里.
     */
    protected $allowedFields = [
        'id' => [
            'whereType'      => 'whereIn',
        ],
        'opportunity_id' => [
            'whereType'      => 'whereIn',
        ],
        'opp_code' => [
            'whereType'      => 'whereIn',
        ],
        'opp_code|opp_name' => [
            'whereType'      => 'whereOr',
        ],
        'owner_rtx|created_by' => [
            'whereType'      => 'whereOr',
        ],
        'data_from' => [
            'whereType'      => 'whereIn',
        ],
        'opp_name'  => [],
        'client_id' => [
            'whereType'      => 'whereIn',
        ],
        'short_id' => [
            'whereType'      => 'whereIn',
        ],
        'agent_id' => [
            'whereType'      => 'whereIn',
        ],
        'brand_id' => [
            'whereType'      => 'whereIn',
        ],
        'belong_to' => [
            'whereType'      => 'whereIn',
        ],
        'is_share' => [
            'whereType'      => 'whereIn',
        ],
        'owner_rtx' => [
            'whereType'      => 'whereIn',
        ],
        'sale_rtx' => [
            'whereType'      => 'whereIn',
        ],
        'channel_rtx' => [
            'whereType'      => 'whereIn',
        ],
        'order_date'            => [],
        'onboard_begin'         => [],
        'onboard_begin_start'   => [
            'filed'          => 'onboard_begin',
            'whereType'      => 'whereDate',
        ],
        'onboard_begin_end' => [
            'filed'          => 'onboard_begin',
            'whereType'      => 'whereDate',
        ],
        'onboard_end'           => [],
        'forecast_money'        => [],
        'forecast_money_remain' => [],
        'step'                  => [],
        'probability'           => [
            'whereType'      => 'whereIn',
        ],
        'manager_probability' => [
            'whereType'      => 'whereIn',
        ],
        'step_comment' => [],
        'risk_type'    => [
            'whereType'      => 'whereIn',
        ],
        'risk_comment'                => [],
        'opp_type'                    => [],
        'status'                      => [],
        'is_crucial'                  => [],
        'crucial_rtx'                 => [],
        'opp_resource'                => [],
        'frame_type'                  => [],
        'help_type'                   => [],
        'help_comment'                => [],
        'close_date'                  => [],
        'close_value'                 => [],
        'close_comment'               => [],
        'order_money'                 => [],
        'order_rate'                  => [],
        'order_rate_real_time'        => [],
        'video_forecast_money'        => [],
        'video_order_money'           => [],
        'video_order_rate'            => [],
        'video_forecast_money_remain' => [],
        'news_forecast_money'         => [],
        'news_order_rate'             => [],
        'news_order_money'            => [],
        'news_forecast_money_remain'  => [],
        'created_by'                  => [
            'whereType'      => 'whereIn',
        ],
        'created_at' => [
            'whereType'      => 'whereDate',
        ],
        'updated_by' => [
            'whereType'      => 'whereIn',
        ],
        'updated_at' => [
            'whereType'      => 'whereDate',
        ],
        'updated_at_start' => [
            'filed'          => 'updated_at',
            'whereType'      => 'whereDate',
        ],
        'updated_at_end' => [
            'filed'          => 'updated_at',
            'whereType'      => 'whereDate',
        ],
    ];

    public function __construct(Request $request)
    {
        $this->params = $this->initParams($request);
        Log::info('params:' . var_export($this->params, 1));

        $this->fields = $this->initFields($request);
        $this->query = Opportunity::query()->select($this->fields);

        $this->buildRequestQuery();

        $this->debug($request);
    }

    private function debug($request)
    {
        if ($request->input('getQuery')) {
            $this->buildPagingQuery();
            dd($this->query->getQuery());
        }
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
        return $this->query->get()->pluck('Fopportunity_id')->toArray();
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
        $this->paging['page'] = $request->input('page', OpportunityDef::DEFAULT_PAGE);
        $this->paging['perPage'] = $request->input('per_page', OpportunityDef::DEFAULT_PER_PAGE);

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
        return $request->input('fields', '*');
    }

    /**
     * 根据请求的参数和可以查询的参数，构造Query.
     */
    protected function buildRequestQuery()
    {
        array_walk($this->params, function ($value, $field) {
            $this->buildFieldQuery($field, $value);
        });
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
    private function buildFieldQuery($field, $value)
    {
        // 查询字段配置项
        $fieldSetting = $this->allowedFields[$field];
        // 映射字段
        $field = $fieldSetting['filed'] ?? $field;
        // 是否允许模糊搜索
        $operator = '=';
        $operator = $fieldSetting['fuzzyMatch'] ?? 'like';
        // 查询类型
        $whereType = $fieldSetting['whereType'] ?? 'where';
        switch ($whereType) {
            case 'whereOr':
                $this->buildWhereOrQuery($field, $value);
                break;
            case 'whereIn':
                $value = explode(',', $value);
                $this->query->whereIn($field, array_wrap($value));
                break;
            case 'whereDate':
                $operator = $this->operator[$field] ?? $operator;
                $this->query->whereDate($field, $operator, $value);
                break;
            case 'where':
            default:
                $operator = $this->operator[$field] ?? $operator;
                $value = $operator == 'like' ? ('%' . $value . '%') : $value;
                $this->query->where($field, $operator, $value);
                break;
        }
    }

    // todo. 待优化
    private function buildWhereOrQuery($field, $value)
    {
        $fields = explode('|', $field);
        foreach ($fields as $key => $item) {
            $operator = $this->getOperator($item);
            $value = $operator === 'like' ? ('%' . $value . '%') : $value;
            $this->query->orWhere($item, $operator, $value);
        }
    }

    /**
     * 获取参数的自定义操作符.
     *
     * @param string $field
     */
    private function getOperator($field)
    {
        return $this->operator[$field] ?? '=';
    }
}
