<?php
namespace App\Repositories\Department;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine DepartmentFilter
 *
 * @apiParam {String} [department_id] 部门id [逗号分隔]
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+department_id] 排序, +id代表按照id升序,-id代表按照id降序
 */
class DepartmentFilter extends ForecastFilter
{
    use SelfName;

    protected $departmentId;

    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->departmentId = $request->get('department_id') === null ? [] : explode(',', $request->get('department_id'));
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort(): string
    {
        return '+department_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,department_id';
    }
}
