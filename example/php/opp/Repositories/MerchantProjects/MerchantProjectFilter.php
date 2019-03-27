<?php

namespace App\Repositories\MerchantProjects;

use Illuminate\Http\Request;

/**
 * @apiDefine MerchantProjectFilter
 * @apiParam {String} project_code 招商项目Code
 * @apiParam {String} project_name 招商项目名称
 * @apiParam {Number} page=1 显示第几页数据
 * @apiParam {Number} per_page=10 每页显示多少条数据
 */
class MerchantProjectFilter
{
    /**
     * @var string
     */
    protected $projectCode;

    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var integer
     */
    protected $perPage;

    /**
     * MerchantProjectFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->projectCode = $request->get('project_code');
        $this->projectName = $request->get('project_name');
        $this->page = intval($request->get('page', 1));
        $this->perPage = intval($request->get('per_page', 10));
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * 转成API搜索条件
     * @return array
     */
    public function toAPIFilter()
    {
        $result = [];
        if ($this->projectCode) {
            $result['Fproject_code'] = is_array($this->projectCode) ? implode(';', $this->projectCode) : $this->projectCode;
        }
        if ($this->projectName) {
            $result['Fproject_name'] = $this->projectName;
        }
        $result['page_size'] = $this->perPage;
        $result['page_index'] = $this->page;
        return $result;
    }

    /**
     * 转成ES搜索条件
     * @return array
     */
    public function toESFilter()
    {
        //TODO 拼写ES查询条件
        return [

        ];
    }
}