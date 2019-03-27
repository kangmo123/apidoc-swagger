<?php
namespace App\Repositories\Sale;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine SaleFilter
 *
 * @apiParam {String} [sale_id] 销售id [逗号分隔]
 * @apiParam {String} [team_id] 小组id
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+sale_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class SaleFilter extends ForecastFilter
{
    use SelfName;

    protected $saleId;

    protected $teamId;

    public function getSaleId()
    {
        return $this->saleId;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->saleId = $request->get('sale_id') === null ? [] : explode(',', $request->get('sale_id'));
        $this->teamId = $request->get('team_id');
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort() :string
    {
        return '+sale_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,sale_id,team_id';
    }
}
