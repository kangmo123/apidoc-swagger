<?php

namespace App\MicroService;

/**
 * Class MerchantClient
 * @package App\MicroService\Revenue
 * @author caseycheng <caseycheng@tencent.com>
 *
 * @method array topn(array $params)
 * @method array merchants(array $params)
 * @method array checkMultiSiteIncome(array $params)
 * @method array total(array $params)
 */
class MerchantClient extends RevenueClient
{
    protected $methods = [
        "topn" => [
            "method" => "get",              //默认是get
            "uri" => "/v1/merchants/topn",
        ],
        "merchants" => [
            "method" => "post",
            "uri" => "/v1/merchants",
        ],
        "checkMultiSiteIncome" => [
            "method" => "get",
            "uri" => "/v1/merchants/check-multi-site-income",
        ],
        "total" => [
            "method" => "get",
            "uri" => "/v1/merchants/total",
        ],
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "revenue.service";
    }
}
