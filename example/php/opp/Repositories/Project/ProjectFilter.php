<?php
namespace App\Repositories\Project;

use Illuminate\Http\Request;
use App\Repositories\SelfName;
use App\Services\ConstDef\OptionDef;
use App\Repositories\ForecastFilter;
use Illuminate\Database\Query\Builder;

/**
 * @apiDefine ProjectFilter
 *
 * @apiParam {String} [client_id] 客户id
 * @apiParam {String} [sale_id] 销售id
 * @apiParam {String} [team_id] 小组id
 * @apiParam {String} [centre_id] 中心id
 * @apiParam {String} [area_id] 片区id
 * @apiParam {String} [department_id] 部门id
 * @apiParam {String} [project_id] 招商项目id [逗号分隔]
 * @apiParam {String} [channel_type=direct] 渠道类型，direct(销售) channel(渠道)
 * @apiParam {Number} [page=1] 页数
 * @apiParam {Number} [per_page=50] 每页的条数,=0时代表返回所有
 * @apiParam {String} [sort=+project_id] 排序规则, +id代表按照id升序,-id代表按照id降序
 */
class ProjectFilter extends ForecastFilter
{
    use SelfName;

    protected $clientId;

    protected $saleId;

    protected $teamId;

    protected $centreId;

    protected $areaId;

    protected $departmentId;

    protected $projectId;

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getSaleId()
    {
        return $this->saleId;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getCentreId()
    {
        return $this->centreId;
    }

    public function getAreaId()
    {
        return $this->areaId;
    }

    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function resolveFromRequest(Request $request)
    {
        $this->clientId = $request->get('client_id');
        $this->saleId = $request->get('sale_id');
        $this->teamId = $request->get('team_id');
        $this->centreId = $request->get('centre_id');
        $this->areaId = $request->get('area_id');
        $this->departmentId = $request->get('department_id');
        $this->projectId = $request->get('project_id') === null ? [] : explode(',', $request->get('project_id'));
    }

    /**
     * 设置默认排序规则
     * @return string, e.g +consume_date...
     */
    public function defaultSort()
    {
        return '+project_id';
    }

    /**
     * 设置默认排序规则
     * @return string
     */
    public function defaultGroupBy() :string
    {
        return 'year,quarter,product_type,sale_id,team_id,client_id,project_id';
    }

    /**
     * 额外过滤条件
     * @param Builder $query
     * @return Builder
     */
    public function additionalQuery(Builder $query)
    {
        return $query->where('coop_type', OptionDef::COOPERATION_MERCHANT)
                ->where('project_id', '>', 0);
    }
}
