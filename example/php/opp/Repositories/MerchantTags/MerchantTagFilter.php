<?php

namespace App\Repositories\MerchantTags;

use Illuminate\Http\Request;

/**
 * @apiDefine MerchantTagFilter
 * @apiParam {String} tag 招商标签名称
 * @apiParam {String} merchant_code 招商项目编码
 * @apiParam {String} policy_grade 政策等级，S(S级) A(A级) B(B级) C(C级)
 * @apiParam {String} include 额外信息(多项逗号分隔)，policy_grades(政策标签) merchant_projects(招商项目)
 * @apiParam {Number} page=1 显示第几页数据
 * @apiParam {Number} per_page=10 每页显示多少条数据
 */
class MerchantTagFilter
{
    /**
     * @var string
     */
    protected $tag;

    /**
     * @var string
     */
    protected $merchantCode;

    /**
     * @var string
     */
    protected $policyGrade;

    /**
     * @var array
     */
    protected $include = [];

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var integer
     */
    protected $perPage;

    /**
     * MerchantTagFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->tag = $request->get('tag');
        $this->merchantCode = $request->get('merchant_code');
        $this->policyGrade = $request->get('policy_grade');
        $this->include = parseCommaString($request->get('include', ''));
        $this->page = intval($request->get('page', 1));
        $this->perPage = intval($request->get('per_page', 10));
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getMerchantCode()
    {
        return $this->merchantCode;
    }

    /**
     * @return string
     */
    public function getPolicyGrade()
    {
        return $this->policyGrade;
    }

    /**
     * @return array
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return integer
     */
    public function getPerPage()
    {
        return $this->perPage;
    }
}