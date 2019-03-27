<?php
namespace App\Repositories\Centre;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine CentreFilter
 *
 * @apiParam {String} [centre_id] 中心id [逗号分隔]
 * @apiParam {String} [area_id] 片区id
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+centre_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class CentreFilter extends ForecastFilter
{
    use SelfName;

    protected $centreId;

    protected $areaId;

    public function getCentreId()
    {
        return $this->centreId;
    }

    public function getAreaId()
    {
        return $this->areaId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->centreId = $request->get('centre_id') === null ? [] : explode(',', $request->get('centre_id'));
        $this->areaId = $request->get('area_id');
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort() :string
    {
        return '+centre_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,centre_id,area_id';
    }
}
