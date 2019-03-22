<?php

namespace App\Services\Planning;

use App\Constant\ProjectConst;
use App\MicroService\ArchitectClient;
use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

/**
 * Class MerchantClient
 * @package App\Services\Merchant
 * @author caseycheng <caseycheng@tencent.com>
 */
class ProposalClient
{
    protected static $methods = [
        'getProposalList' => 'planning/proporsal/searchproporsal',
    ];

    protected static $configs = [
        'home' => [
            'host' => 'openarps.test.addev.com',
            'appKey' => '8dfaecdc212708f7',
            'appSecrete' => '2cc084199bff46c6a17ce132abdc4a65',
        ],
        'dev' => [
            'host' => 'openarps.test.addev.com',
            'appKey' => '8dfaecdc212708f7',
            'appSecrete' => '2cc084199bff46c6a17ce132abdc4a65',
        ],
//        'pretest' => [
//            'host' => 'pretest.open.addev.com',
//            'appKey' => '4ba1c09b3695e0cb',
//            'appSecrete' => 'bf97a0cd381b6ab2fb92baf5d06cf2a5',
//        ],
        //todo::暂时调用planning线上接口，要不然都没数据
        'pretest' => [
            'host' => 'open.addev.com',
            'appKey' => '4ba1c09b3695e0cb',
            'appSecrete' => 'bf97a0cd381b6ab2fb92baf5d06cf2a5',
        ],
        'production' => [
            'host' => 'open.addev.com',
            'appKey' => '4ba1c09b3695e0cb',
            'appSecrete' => 'bf97a0cd381b6ab2fb92baf5d06cf2a5',
        ],
    ];

    protected $headers = [];

    /**
     * @var ClientInterface
     */
    protected $client = null;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param $uri
     * @param $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($uri, $params)
    {
        $env = app()->environment();
        $config = self::$configs[$env];

        if ('dev' == $env) {
            $host = "10.123.8.10";
            $url = "http://{$host}/{$uri}";
            $this->headers["Host"] = $config['host'];
        } else {
            $url = "http://{$config['host']}/{$uri}";
        }

        $random = rand(1000, 9999);
        $time = time();
        $this->headers = \array_merge($this->headers, [
            'oauth-app-key' => $config['appKey'],
            'oauth-token' => md5($config['appKey'] . $config['appSecrete'] . $time . $random),
            'oauth-time' => $time,
            'oauth-random' => $random,
        ]);
        $options = $this->makePostOptions($params);
        $options['timeout'] = 10;
        return $this->request('POST', $url, $options);
    }

    /**
     * @param $method
     * @param $url
     * @param $options
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method, $url, $options)
    {
        try {
            $resp = $this->client->request($method, $url, $options);
            $ret = (string)$resp->getBody();
            return json_decode($ret, true);
        } catch (RequestException $e) {
            throw $e;
        }
    }

    protected function makeGetOptions($params)
    {
        return $this->makeOptions($params, RequestOptions::QUERY);
    }

    protected function makePostOptions($params)
    {
        return $this->makeOptions($params, RequestOptions::FORM_PARAMS);
    }

    protected function makeOptions($params, $paramKey)
    {
        $options = [
            $paramKey => $params,
            RequestOptions::HTTP_ERRORS => true,
        ];
        if (!empty($this->headers)) {
            $options[RequestOptions::HEADERS] = $this->headers;
        }
        return $options;
    }

    /**
     * 根据排期code批量获取排期
     *
     * @param $proposalCodeList
     * @param int $perPage
     * @param string $channelType
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProposalListByCode(
        $proposalCodeList,
        $perPage = ProjectConst::DEFAULT_PAGE_SIZE,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        $params = [
            'pro_code' => implode(';', $proposalCodeList),
            'page_size' => $perPage,
        ];
        $method = self::$methods['getProposalList'];
        $ret = $this->post($method, $params);

        if ($ret['code'] != 0) {
            Log::warning("get proposal list by code error: " . $ret['msg']);
            return [];
        }

        $data = $ret['data'];
        $records = $data['records'] ?? [];
        $ret = [];

        if (empty($records)) {
            return $ret;
        }

        $searchBegin = Carbon::today();
        $searchEnd = Carbon::today();

        foreach ($records as $v) {
            $begin = Carbon::make($v["Fbegin_time"]);
            $end = Carbon::make($v["Fend_time"]);
            $beginDate = (clone $begin)->format("Y-m-d");
            $endDate = (clone $end)->format("Y-m-d");
            $searchBegin = min($searchBegin, $begin);
            $searchEnd = max($searchEnd, $end);
            $baseInfo = [
                "code" => $v["Fpro_code"],
                "name" => $v["Fpro_name"],
                "proposal_id" => $v["Fpro_id"],
                "proposal_code" => $v["Fpro_code"],
                "ability" => $v["Fschedule_ability"],
                "area" => $v["FsaleRange"]["area"] ?? [],
                "total_money" => $v["Fpro_money"],
                "start_time" => $beginDate,
                "end_time" => $endDate,
            ];

            if (ProjectConst::SALE_CHANNEL_TYPE_DIRECT == $channelType) {
                $saleInfo = $v["FsaleRange"]["sale"] ?? "";
                $teamInfo = $v["FsaleRange"]["sale_team"] ?? "";
            } else {
                $saleInfo = $v["FsaleRange"]["channel"] ?? "";
                $teamInfo = $v["FsaleRange"]["channel_team"] ?? "";
            }

            if (!is_null(json_decode($saleInfo))) {
                $saleInfo = json_decode($saleInfo, true);
            }

            if (!is_null(json_decode($teamInfo))) {
                $teamInfo = json_decode($teamInfo, true);
            }

            $timeLineInfo = [
                "sale_time_line" => $saleInfo,
                "team_time_line" => $teamInfo,
            ];
            $ret[$baseInfo["code"]] = \array_merge($baseInfo, $timeLineInfo);
        }
        return $ret;
    }
}
