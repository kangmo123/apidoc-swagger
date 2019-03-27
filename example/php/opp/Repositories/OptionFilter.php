<?php
/**
 * 商机配置项过滤器
 * User: hubertchen
 * Date: 2018/11/29
 * Time: 19:08.
 */

namespace App\Repositories;

use App\Services\ConstDef\OptionDef;
use Illuminate\Http\Request;

/**
 * @apiDefine       SearchOptionsParams
 * @apiParam {Number} type=2 配置项类型编码，非必填
 * @apiParam {String} keyword='花无缺' 查询关键词，非必填
 */

/**
 * @apiDefine       SearchOptionsParamsExample
 * @apiParamExample 根据类型获取配置项参数示例
 * {
 *      "type": 1,
 *      "keyword": '花无缺',
 * }
 */
class OptionFilter
{
    protected $type;
    protected $keyword;
    protected $page;
    protected $perPage;
    protected $sort;

    public function __construct(Request $request)
    {
        $this->keyword = $request->get('keyword');
        $this->type = array_wrap(explode(',', $request->get('type')));
        $this->page = (int) $request->get('page', OptionDef::DEFAULT_PAGE);
        $this->perPage = (int) $request->get('per_page', OptionDef::DEFAULT_PER_PAGE);
        $this->sort = parseSortString($request->get('sort', '-id'));
    }

    // 获取并过滤可筛选的 type
    public function getType()
    {
        return array_intersect($this->type, array_keys(OptionDef::OPTIONS_MAP));
    }

    public function getKeyword()
    {
        return $this->keyword;
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
