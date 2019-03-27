<?php
namespace App\Repositories\Area;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine AreaFilter
 *
 * @apiParam {String} [area_id] 片区id [逗号分隔]
 * @apiParam {String} [department_id] 部门id
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+area_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class AreaFilter extends ForecastFilter
{
    use SelfName;

    protected $areaId;

    protected $departmentId;

    public function getAreaId()
    {
        return $this->areaId;
    }

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
        $this->areaId = $request->get('area_id') === null ? [] : explode(',', $request->get('area_id'));
        $this->departmentId = $request->get('department_id');
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort() :string
    {
        return '+area_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,area_id,department_id';
    }
}
