<?php
/**
 * 商机阶段过滤器
 * User: hubertchen
 * Date: 2018/11/27
 * Time: 21:26
 */

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Services\ConstDef\OpportunityDef;

/**
 * @apiDefine ListProcessParams
 * @apiParam {String} opportunity_id 根据 opportunity_id 筛选，必填
 * @apiParam {Number} page=1 显示第几页数据，非必填
 * @apiParam {Number} per_page=20 每页显示多少条记录，非必填
 * @apiParam {String} sort=-id 排序规则，-id表示按id倒序，非必填
 */

/**
 * @apiDefine       ListProcessParamExample
 * @apiParamExample 查询商机阶段参数示例
 * {
 *      "opportunity_id": "金城武",
 *      "page": 1,
 *      "per_page": 15,
 * }
 */
class ProcessFilter
{
    protected $opportunityId;
    protected $page;
    protected $perPage;
    protected $sort;

    public function __construct(Request $request, $opportunity_id)
    {
        $this->opportunityId     = $opportunity_id;
        $this->page              = (int)$request->get('page', OpportunityDef::DEFAULT_PAGE);
        $this->perPage           = (int)$request->get('per_page', OpportunityDef::DEFAULT_PER_PAGE);
        $this->sort              = parseSortString($request->get('sort', '-id'));
    }

    public function getOpportunityId()
    {
        return $this->opportunityId;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getSort()
    {
        return $this->sort;
    }
}
