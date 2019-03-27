<?php
namespace App\Repositories\Team;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;

/**
 * @apiDefine TeamFilter
 *
 * @apiParam {String} [team_id] 小组id [逗号分隔]
 * @apiParam {String} [centre_id] 中心id
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+team_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class TeamFilter extends ForecastFilter
{
    use SelfName;

    protected $teamId;

    protected $centreId;

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getCentreId()
    {
        return $this->centreId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->teamId = $request->get('team_id') === null ? [] : explode(',', $request->get('team_id'));
        $this->centreId = $request->get('centre_id');
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort()
    {
        return '+team_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,team_id,centre_id';
    }
}
