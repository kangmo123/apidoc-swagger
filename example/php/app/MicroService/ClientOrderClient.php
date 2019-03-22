<?php

namespace App\MicroService;

use App\Constant\ProjectConst;
use Carbon\Carbon;

/**
 * Class ClientOrderClient
 *
 * @method array clientOverview(array $params)
 * @method array clientDetail(array $params)
 * @method array proposalsSearch(array $params)
 * @method array effectsSearch(array $params)
 * @method array remainProposals(array $params)
 * @method array updateTime(array $params)
 * @package App\MicroService
 */
class ClientOrderClient extends Client
{
    const REVENUE_DEFAULT_SIZE = 0;
    const REVENUE_DEFAULT_PAGE = 1;

    protected $methods = [
        'clientOverview' => [
            'type' => 'get',
            'uri' => '/v1/revenue/clients/overview'
        ],
        'clientDetail' => [
            'type' => 'get',
            'uri' => '/v1/revenue/clients/detail'
        ],
        'proposalsSearch' => [
            'type' => 'get',
            'uri' => '/v1/revenue/proposals/search'
        ],
        'effectsSearch' => [
            'type' => 'get',
            'uri' => '/v1/revenue/effects/search'
        ],
        'remainProposals' => [
            'type' => 'get',
            'uri' => '/v1/revenue/proposals/remain'
        ],
        'updateTime' => [
            'type' => 'get',
            'uri' => '/v1/status/tagging/uptime'
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
