<?php
namespace App\Repositories\Client;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Repositories\ForecastFilter;
use App\Services\ConstDef\OpportunityDef;

/**
 * @apiDefine ClientFilter
 *
 * @apiParam {String} [sale_id] 销售id
 * @apiParam {String} [team_id] 小组id
 * @apiParam {String} [agent_id] 代理商id
 * @apiParam {String} [client_id] 客户id [逗号分隔]
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+client_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class ClientFilter extends ForecastFilter
{
    use SelfName;

    protected $saleId;

    protected $teamId;

    protected $agentId;

    protected $clientId;

    public function getSaleId()
    {
        return $this->saleId;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getAgentId()
    {
        return $this->agentId;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->saleId = $request->get('sale_id');
        $this->teamId = $request->get('team_id');
        $this->agentId = $request->get('agent_id');
        $this->clientId = $request->get('client_id') === null ? [] : explode(',', $request->get('client_id'));
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort()
    {
        return '+client_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        if ($this->getSaleType() == OpportunityDef::BELONG_TO_SALE) {
            return 'year,quarter,product_type,sale_id,team_id,client_id';
        } else {
            return 'year,quarter,product_type,sale_id,team_id,agent_id,client_id';
        }
    }
}
