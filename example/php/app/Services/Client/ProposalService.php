<?php

namespace App\Services\Client;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\MicroService\ArchitectClient;
use App\MicroService\ClientClient;
use App\MicroService\ClientOrderClient;
use App\MicroService\RevenueClient;
use App\Services\Planning\ProposalClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Utils\TreeUtil;

class ProposalService
{
    protected $productTypeSummaryMap = [
        'brand_total' => RevenueConst::PRODUCT_TYPE_ALL,
        'brand_video' => RevenueConst::PRODUCT_TYPE_VIDEO,
        'brand_news' => RevenueConst::PRODUCT_TYPE_NEWS,
        'brand_sns_contract' => RevenueConst::PRODUCT_TYPE_SNS_CONTRACT,
        'brand_other' => RevenueConst::PRODUCT_TYPE_OTHER,
    ];

    protected $proposalCodeList = [];

    protected $exportDesc = "";

    protected $searchDataHeader = [
        "排期编码",
        "排期名称",
        "客户简称",
        "客户全称",
        "品牌名称",
        "产品名称",
        "销售负责人-时间轴",
        "客户小组-时间轴",
        "整单收入",
        "是否已实结",
        "品牌总下单(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "品牌-腾讯视频下单(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "品牌-腾讯新闻下单(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "品牌-合约朋友圈下单(单位： " . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "品牌-其他品牌下单(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "其他(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
    ];

    protected $remainDataHeader = [
        "排期编码",
        "排期名称",
        "整单收入(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "预定收入(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "已计收入(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "未计收入(单位：" . RevenueConst::DOWNLOAD_MONEY_UNIT_NAME . ")",
        "客户全称",
        "品牌名称",
        "产品名称",
        "排期销售-时间轴",
        "排期小组-时间轴",
        "排期投放时间",
    ];

    /**
     * 获取排期列表信息
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param null $saleId
     * @param null $teamId
     * @param null $proposalCode
     * @param int $page
     * @param int $perPage
     * @param string $channelType
     * @param bool $exportFlag
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProposalSearchList(
        $clientId,
        $begin,
        $end,
        $saleId = null,
        $teamId = null,
        $proposalCode = null,
        $page = ProjectConst::DEFAULT_PAGE,
        $perPage = ProjectConst::DEFAULT_PAGE_SIZE,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $exportFlag = false
    ) {
        /**
         * @var $revenueClient ClientOrderClient
         */
        $revenueClient = app(ClientOrderClient::class);
        if ($exportFlag) {
            $params = [
                'client_id' => $clientId,
                'begin' => $begin,
                'end' => $end,
                'sale_id' => $saleId,
                'team_id' => $teamId,
                'schedule_code' => $proposalCode,
                'channel_type' => $channelType,
                'per_page' => 0,
            ];
        } else {
            $params = [
                'client_id' => $clientId,
                'begin' => $begin,
                'end' => $end,
                'sale_id' => $saleId,
                'team_id' => $teamId,
                'schedule_code' => $proposalCode,
                'channel_type' => $channelType,
                'page' => $page,
                'per_page' => $perPage,
            ];
        }

        $ret = $revenueClient->proposalsSearch($params);
        $data = $ret['data'] ?? [];
        $pageInfo = $ret['page_info'] ?? [];
        unset($ret);

        if (empty($data)) {
            return [[], [], []];
        }

        $proposalListInfo = $clientBrandInfo = [];
        $brandIdList = \array_unique(\array_column($data, "brand_id"));
        $proposalCodeList = \array_unique(\array_column($data, "schedule_code"));

        if ($brandIdList) {
            $client = app(ClientClient::class);
            $clientBrandInfo = $client->getBrandInfoBatch("brand_id", $brandIdList, 2000);
        }

        if ($proposalCodeList) {
            $proposalListInfo = $this->getProposalListByCodeList($proposalCodeList, $channelType);
        }

        $ret = $this->formatSearchData($data, $clientBrandInfo, $proposalListInfo);

        if ($exportFlag) {
            $descInfo = $this->completeExportHeader($clientId, $begin, $end, $saleId, $teamId, $proposalCode,
                $channelType);
            $this->exportDesc = "客户下单品牌排期数据(筛选条件: {$descInfo})";
            $ret = $this->formatSearchExportData($ret);
            return $ret;
        } else {
            return [$ret, $pageInfo];
        }
    }

    /**
     * 格式化搜索数据
     *
     * @param $data
     * @param $clientBrandInfo
     * @param $proposalListInfo
     * @return array
     */
    protected function formatSearchData($data, $clientBrandInfo, $proposalListInfo)
    {
        $ret = [];
        if (empty($data)) {
            return $ret;
        }

        foreach ($data as $value) {
            $brandInfo = [];
            if (!empty($clientBrandInfo) && \array_key_exists($value['brand_id'], $clientBrandInfo)) {
                $brandData = $clientBrandInfo[$value['brand_id']];
                $clientInfo = $brandData["client"];
                $brandInfo = [
                    'brand_name' => $brandData["brand_name"] ?? "-",
                    'product_name' => $brandData["product_name"] ?? "-",
                    'client_name' => $brandData["client_name"],
                    'short_name' => $clientInfo["short_name"] ?? "-",
                ];
            }
            $proposalInfo = $proposalListInfo[$value["schedule_code"]] ?? [];
            $proposalData = [
                "proposal_money" => $proposalInfo["total_money"] ?? null,
                "sale_data" => $proposalInfo["sale_data"] ?? [],
                "team_data" => $proposalInfo["team_data"] ?? [],
                "sale_time_line" => $proposalInfo["sale_time_line"] ?? null,
                "team_time_line" => $proposalInfo["team_time_line"] ?? [],
            ];
            $costData = $this->fillCostData($value['cost_data']);
            unset($value['cost_data']);
            $value['settled'] = intval($value['settled']);
            $value['schedule_code'] = strval($value['schedule_code']);
            $ret[] = \array_merge($value, $brandInfo, $costData, $proposalData);
        }

        return $ret;
    }

    /**
     * 填充需要展示的品牌产品字段
     *
     * @param $costData
     * @return array
     */
    protected function fillCostData($costData)
    {
        $ret = [];
        $total = 0;

        foreach ($costData as $v) {
            $total += $v['cost'];
        }

        foreach ($this->productTypeSummaryMap as $key => $value) {
            $nodeList = TreeUtil::getLeafNodesByIndexRecursively(RevenueConst::$productTypeMergeTree, $value);
            $ret[$key] = 0;

            if (empty($costData) || empty($nodeList)) {
                continue;
            }

            foreach ($costData as $v) {
                $productType = $v['product_type'];

                if (\in_array($productType, $nodeList)) {
                    $ret[$key] += $v['cost'];
                }
            }
        }

        $ret['other'] = $total - $ret['brand_total'];
        return $ret;
    }

    /**
     * 导出品牌排期列表数据
     *
     * @param $data
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportSearchData($data)
    {
        $filename = "客户下单品牌排期数据";
        return $this->doExport($data, $filename, $this->searchDataHeader);

    }

    /**
     * 导出已下单未计入排期
     *
     * @param $data
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportRemainSearchData($data)
    {
        $filename = "已下单但未计入业绩数据";
        return $this->doExport($data, $filename, $this->remainDataHeader);
    }

    /**
     * 导出生成文件
     *
     * @param $data
     * @param $filename
     * @param $header
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function doExport($data, $filename, $header)
    {
        $exportFileName = "{$filename}_" . Carbon::now()->format("YmdHis") . ".xlsx";
        $exportFilePath = "/tmp/{$exportFileName}";

        if (!empty($this->exportDesc)) {
            $rows[] = [$this->exportDesc];
        }

        $rows[] = $header;

        if (!empty($data)) {
            foreach ($data as $datum) {
                if (is_array($datum)) {
                    $rows[] = array_values($datum);
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $length = count($this->proposalCodeList);
        $sheet->getStyle("A3:A{$length}")
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->fromArray($rows);

        for ($i = 0; $i < $length; $i++) {
            $index = $i + 3;
            $sheet->setCellValueExplicit("A{$index}", $this->proposalCodeList[$i],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($exportFilePath);
        return [
            'file' => $exportFilePath,
            'name' => $exportFileName,
            'header' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        ];
    }

    /**
     * 格式化排期列表导出数据
     *
     * @param $data
     * @return array
     */
    protected function formatSearchExportData($data)
    {
        $ret = [];
        if (empty($data)) {
            return $ret;
        }

        foreach ($data as $value) {
            $money = $value["proposal_money"] ?? 0;
            $brandName = $value['brand_name'] ?? "";
            $productName = $value["product_name"] ?? "";
            $code = $value["schedule_code"] ?? "-";
            $ret[] = [
                $code,
                $value["schedule_name"] ?? "-",
                $value["short_name"] ?? "-",
                $value["client_name"] ?? "-",
                $brandName,
                $productName,
                $value["sale_time_line"] ?? null,
                $value["team_time_line"] ?? null,
                round($money / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                !empty($value["settled"]) ? "是" : "否",
                round($value["brand_total"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["brand_video"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["brand_news"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["brand_sns_contract"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["brand_other"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["other"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
            ];
            $this->proposalCodeList[] = $code;
        }

        return $ret;

    }

    /**
     * 获取已下单未计入收入排期数据
     *
     * @param $clientId
     * @param $begin
     * @param $end
     * @param null $saleId
     * @param null $teamId
     * @param null $proposalCode
     * @param int $page
     * @param int $perPage
     * @param string $channelType
     * @param bool $exportFlag
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRemainProposalSearchList(
        $clientId,
        $begin,
        $end,
        $saleId = null,
        $teamId = null,
        $proposalCode = null,
        $page = ProjectConst::DEFAULT_PAGE,
        $perPage = ProjectConst::DEFAULT_PAGE_SIZE,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
        $exportFlag = false
    ) {
        $revenueClient = app(ClientOrderClient::class);
        if ($exportFlag) {
            $params = [
                'client_id' => $clientId,
                'begin' => $begin,
                'end' => $end,
                'sale_id' => $saleId,
                'team_id' => $teamId,
                'schedule_code' => $proposalCode,
                'channel_type' => $channelType,
                'per_page' => 0,
            ];
        } else {
            $params = [
                'client_id' => $clientId,
                'begin' => $begin,
                'end' => $end,
                'sale_id' => $saleId,
                'team_id' => $teamId,
                'schedule_code' => $proposalCode,
                'channel_type' => $channelType,
                'page' => $page,
                'per_page' => $perPage,
            ];
        }
        $ret = $revenueClient->remainProposals($params);
        $data = $ret['data'] ?? [];
        $summary = $data['summary'] ?? 0;
        $list = $data['list'] ?? [];
        $pageInfo = $ret['page_info'] ?? [];
        unset($data);

        if (empty($list)) {
            return [0, [], []];
        }

        $clientBrandInfo = [];
        $brandIdList = \array_unique(\array_column($list, "brand_id"));
        $proposalCodeList = \array_unique(\array_column($list, "schedule_code"));

        if ($brandIdList) {
            $client = app(ClientClient::class);
            $clientBrandInfo = $client->getBrandInfoBatch("brand_id", $brandIdList, 2000);
        }

        $proposalListInfo = [];

        if ($proposalCodeList) {
            $proposalListInfo = $this->getProposalListByCodeList($proposalCodeList, $channelType);
        }

        $ret = $this->formatSearchRemainData($list, $clientBrandInfo, $proposalListInfo);

        if ($exportFlag) {
            $descInfo = $this->completeExportHeader($clientId, $begin, $end, $saleId, $teamId, $proposalCode,
                $channelType);
            $this->exportDesc = "已下单但未计入业绩数据(筛选条件: {$descInfo})";
            $ret = $this->formatSearchRemainExportData($ret);
            return $ret;
        } else {
            return [$summary, $ret, $pageInfo];
        }
    }

    /**
     * 格式化已下单未计入收入排期数据
     *
     * @param $data
     * @param $clientBrandInfo
     * @param $proposalListInfo
     * @return array
     */
    protected function formatSearchRemainData($data, $clientBrandInfo, $proposalListInfo)
    {
        $ret = [];
        if (empty($data)) {
            return $ret;
        }

        foreach ($data as $value) {
            $brandInfo = [];
            if (!empty($clientBrandInfo) && \array_key_exists($value['brand_id'], $clientBrandInfo)) {
                $brandData = $clientBrandInfo[$value['brand_id']];
                $clientInfo = $brandData["client"];
                $brandInfo = [
                    'brand_name' => $brandData["brand_name"] ?? "-",
                    'product_name' => $brandData["product_name"] ?? "-",
                    'client_name' => $brandData["client_name"],
                    'short_name' => $clientInfo["short_name"] ?? "-",
                ];
            }
            $proposalInfo = $proposalListInfo[$value["schedule_code"]] ?? [];
            $proposalData = [
                "proposal_money" => $proposalInfo["total_money"] ?? null,
                "sale_data" => $proposalInfo["sale_data"] ?? [],
                "team_data" => $proposalInfo["team_data"] ?? [],
                "sale_time_line" => $proposalInfo["sale_time_line"] ?? null,
                "team_time_line" => $proposalInfo["team_time_line"] ?? [],
                'proposal_time' => !isset($proposalInfo["start_time"]) ? "-" : "{$proposalInfo["start_time"]}～{$proposalInfo["end_time"]}",
            ];
            $costData = $this->fillRemainCostData($value['cost_data']);
            unset($value['cost_data']);
            $ret[] = \array_merge($value, $brandInfo, $costData, $proposalData);
            $this->proposalCodeList[] = $value["schedule_code"];
        }

        return $ret;
    }

    /**
     * 格式化已下单未计入收入排期导出数据
     *
     * @param $data
     * @return array
     */
    protected function formatSearchRemainExportData($data)
    {
        $ret = [];
        if (empty($data)) {
            return $ret;
        }

        foreach ($data as $value) {
            $brandName = $value['brand_name'] ?? "";
            $productName = $value["product_name"] ?? "";
            $ret[] = [
                $value["schedule_code"],
                $value["schedule_name"],
                round($value["proposal_money"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["buy"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["cost"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                round($value["remain_total"] / RevenueConst::DOWNLOAD_MONEY_UNIT_RATIO),
                $value["client_name"],
                $brandName,
                $productName,
                $value["sale_time_line"] ?? "-",
                $value["team_time_line"] ?? "-",
                $value["proposal_time"],
            ];
        }
        return $ret;

    }

    /**
     * 填充已下单未计入收入排期信息
     *
     * @param $costData
     * @return array
     */
    protected function fillRemainCostData($costData)
    {
        $buyTotal = $costTotal = $remain = 0;

        foreach ($costData as $v) {
            $costTotal += $v['cost'];
            $buyTotal += $v['buy'];
        }

        $remain = $buyTotal - $costTotal;
        $ret = [
            "buy" => $buyTotal,
            "cost" => $costTotal,
            "remain_total" => $remain,
        ];
        return $ret;
    }

    /**
     * 获取排期的时间轴、投放时间、整单收入等信息
     *
     * @param $proposalCodeList
     * @param string $channelType
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getProposalListByCodeList(
        $proposalCodeList,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        if (empty($proposalCodeList)) {
            return $proposalCodeList;
        }

        /**
         * @var $proposalClient ProposalClient
         */
        $proposalClient = app(ProposalClient::class);
        $batchSize = 100;
        $records = [];

        for ($i = 0, $step = ceil(count($proposalCodeList) / $batchSize); $i < $step; $i++) {
            /**
             * 根据planning接口，拿排期原始数据
             */
            $proposalSlice = \array_slice($proposalCodeList, $i * $batchSize, $batchSize);
            $tmp = $proposalClient->getProposalListByCode($proposalSlice, $batchSize, $channelType);
            if (!empty($tmp)) {
                foreach ($tmp as $code => $value) {
                    $records[$code] = $value;
                }
            }
        }

        $this->handleTeamTimeLine($records);
        return $records;
    }


    /**
     * 处理排期的销售、小组时间轴
     *
     * @param $records
     * @return mixed
     */
    protected function handleTeamTimeLine(&$records)
    {
        list($records, $teamInfoList) = $this->getTeamInfo($records);

        if (!empty($records)) {
            foreach ($records as &$value) {
                $teamTmp = $value["team_data_tmp"] ?? [];
                $teamTimeStrArr = $teamTime = [];
                if (!empty($teamTmp)) {
                    foreach ($teamTmp as $vv) {
                        /**
                         * 为啥我们这儿的guid是大写，到了planning变成小写
                         */
                        $teamId = mb_strtoupper($vv["Fvalue"]);
                        $teamArr = $teamInfoList[$teamId] ?? [];

                        if (empty($teamArr)) {
                            continue;
                        }

                        $begin = $vv["Fbegin_date"] ?? $value["start_time"];
                        $end = $vv["Fend_date"] ?? $value["end_time"];

                        $findFlag = false;
                        foreach ($teamArr as $record) {

                            if ($begin >= $record["begin"] && $end <= $record["end"]) {
                                /**
                                 * 坑爹的接口，只有一个小组的时候，没有时间，需要自己填上去
                                 */
                                $teamTime[] = [
                                    "team_name" => $record["name"],
                                    "begin" => $begin,
                                    "end" => $end,
                                ];
                                $teamTimeStrArr[] = "{$record["name"]}  {$begin}～{$end}";
                                $findFlag = true;
                                break;
                            }
                        }

                        /**
                         * 针对小组改名字的特殊情况，排期横跨几个改名期的，取最新的小组名称
                         */
                        if (!$findFlag) {
                            $tmpArr = [];
                            foreach ($teamArr as $record) {
                                $tmpBegin = $record["begin"];
                                if ($tmpBegin <= $record["begin"] && ($begin >= $record["begin"] || $end <= $record["end"])) {
                                    $tmpArr = [
                                        "team_name" => $record["name"],
                                        "begin" => $begin,
                                        "end" => $end,
                                    ];
                                }
                            }
                            $teamTime[] = $tmpArr;
                            $teamTimeStrArr[] = "{$tmpArr["team_name"]}  {$begin}～{$end}";
                        }
                    }
                }
                unset($value["team_data_tmp"]);
                $value["team_data"] = $teamTime;
                $value["team_time_line"] = implode(";", $teamTimeStrArr);;
            }
        }
        return $records;
    }

    /**
     * @param $records
     * @return array
     */
    protected function getTeamInfo($records)
    {
        $teamIdList = [];
        $searchBegin = Carbon::today();
        $searchEnd = Carbon::today();

        /**
         * 初步处理，拿销售时间轴
         */
        foreach ($records as &$v) {
            $begin = Carbon::make($v["start_time"]);
            $end = Carbon::make($v["end_time"]);
            $beginDate = (clone $begin)->format("Y-m-d");
            $endDate = (clone $end)->format("Y-m-d");
            $searchBegin = min($searchBegin, $begin);
            $searchEnd = max($searchEnd, $end);
            $saleInfo = $v["sale_time_line"];
            $teamInfo = $v["team_time_line"];

            $saleTimeLineArr = [];
            $saleTimeLineStr = "";
            if (!empty($saleInfo)) {
                if (\is_array($saleInfo)) {
                    $tmp = [];
                    $tmpStr = [];
                    foreach ($saleInfo as $vv) {
                        $tmpStr[] = "{$vv['Fvalue']}  {$vv['Fbegin_date']}～{$vv['Fend_date']}";
                        $tmp[] = [
                            "fullname" => $vv['Fvalue'],
                            "begin" => $vv['Fbegin_date'],
                            "end" => $vv['Fend_date'],
                        ];
                    }
                    $saleTimeLineArr = $tmp;
                    $saleTimeLineStr .= implode(";", $tmpStr);
                } else {
                    $saleTimeLineArr[] = [
                        "fullname" => $saleInfo,
                        "begin" => $beginDate,
                        "end" => $endDate,
                    ];
                    $saleTimeLineStr = "{$saleInfo}  {$beginDate}～{$endDate}";
                }
            }

            $teamInfoArr = [];

            if (!empty($teamInfo)) {
                if (\is_array($teamInfo)) {
                    $teamInfoArr = $teamInfo;
                    $teamIdList = \array_column($teamInfoArr, "Fvalue");
                } else {
                    $teamIdList[] = $teamInfo;
                    $teamInfoArr[] = [
                        "Fvalue" => $teamInfo
                    ];
                }
            }

            $v["sale_time_line"] = $saleTimeLineStr;
            $v["team_time_line"] = "";
            $v["team_time_line_tmp"] = $teamInfoArr;
            $v["sale_data"] = $saleTimeLineArr;
            $v["team_data"] = [];
            $v["team_data_tmp"] = $teamInfoArr;
        }
        $teamIdList = \array_unique($teamIdList);
        $result = [];

        if (!empty($teamIdList)) {
            $list = [];
            foreach ($teamIdList as $id) {
                $list[] = mb_strtoupper($id);
            }

            /**
             * 继续处理，根据team id拿小组时间轴信息
             */
            $architectClient = app(ArchitectClient::class);
            $params = [
                "team_id" => implode(",", $list),
                "begin_date" => (clone $searchBegin)->format("Y-m-d"),
                "end_date" => (clone $searchEnd)->format("Y-m-d"),
                "per_page" => 0,
            ];
            $result = $architectClient->getTeamList($params);
        }

        $teamInfoTmp = $result["data"] ?? [];
        $teamInfoList = [];

        if (!empty($teamInfoTmp)) {
            foreach ($teamInfoTmp as $data) {
                $teamInfoList[$data["team_id"]][] = $data;
            }
        }

        return [$records, $teamInfoList];
    }

    /**
     * @param $clientId
     * @param $begin
     * @param $end
     * @param null $saleId
     * @param null $teamId
     * @param null $proposalCode
     * @param string $channelType
     * @return string
     */
    public function completeExportHeader(
        $clientId,
        $begin,
        $end,
        $saleId = null,
        $teamId = null,
        $proposalCode = null,
        $channelType = ProjectConst::SALE_CHANNEL_TYPE_DIRECT
    ) {
        $descInfoArr[] = "时间范围：{$begin}-{$end}";

        if (!empty($clientId)) {
            /**
             * @var $clientClient ClientClient
             */
            $clientClient = app(ClientClient::class);
            $ret = $clientClient->getClient([
                'client_id' => $clientId,
            ]);

            if ($ret['code'] == 0 && count($ret['data']) == 1) {
                $descInfoArr[] = "客户: " . $ret['data'][0]['client_name'];
            }
        }

        if (!empty($saleId) || !empty($teamId)) {
            /**
             * @var $architectClient ArchitectClient
             */
            $architectClient = app(ArchitectClient::class);

            if (!empty($teamId)) {
                $ret = $architectClient->getTeamInfo($teamId, $begin, $end);
                $descInfoArr[] = "直客小组: " . $ret[0]['name'] ?? "-";
            }

            if (!empty($saleId)) {
                $ret = $architectClient->getSaleInfo($saleId);

                if ($ret['code'] == 0) {
                    $descInfoArr[] = "直客销售: " . $ret['data']['name'];
                }
            }
        }

        if (!empty($proposalCode)) {
            $descInfoArr[] = "排期编码: " . $proposalCode;
        }

        /**
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);
        $updateTime = $clientService->getUpdateTime($channelType);
        $descInfoArr[] = "数据截止时间: " . $updateTime;
        $descInfoArr[] = "单位: 千元";
        return implode(",", $descInfoArr);
    }
}