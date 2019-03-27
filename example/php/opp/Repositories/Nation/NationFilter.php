<?php
namespace App\Repositories\Nation;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine NationFilter
 *
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+area_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class NationFilter extends ForecastFilter
{
    use SelfName;

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultSort() :string
    {
        return '+department_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type';
    }
}
