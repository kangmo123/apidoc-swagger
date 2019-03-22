<?php

namespace App\MicroService;

/**
 * Class ClientClient
 * @package App\MicroService\Client
 * @author ringechchen <ringechchen@tencent.com>
 *
 * @method array getBrand($params)
 * @method array getClient($params)
 */
class ClientClient extends Client
{
    protected $methods = [
        "getBrand" => [
            "method" => "get",
            "uri" => "/v1/brands/search",
        ],
        "getClient" => [
            "method" => "get",
            "uri" => "/v1/clients/search",
        ],
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "client.service";
    }

    /**
     * @param $key
     * @param $idList
     * @param $perPage
     * @return array
     */
    public function getBrandInfoBatch($key, $idList, $perPage)
    {
        $param = [
            $key => implode(",", $idList),
            'per_page' => $perPage
        ];
        $data = $this->getBrand($param);
        $ret = [];

        if (empty($data) || empty($data['data'])) {
            return $ret;
        }

        foreach ($data['data'] as $value) {
            $ret[$value['brand_id']] = $value;
        }

        return $ret;
    }
}
