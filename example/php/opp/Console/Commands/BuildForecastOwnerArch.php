<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use App\Services\DataStore\MysqlStore;
use App\Services\ConstDef\ArchitectDef;
use App\Services\ConstDef\OpportunityDef;

class BuildForecastOwnerArch extends Command
{
    protected $signature = 'build:forecast-owner-arch
                                {--year= : Build forecasts from which year}
                                {--quarter= : Build forecasts from which quarter}';

    protected $description = 'Build forecast owner architecture';

    protected $storageTableName = 't_opp_forecast_owner_arch';

    protected $cacheKeyPrefix = 'sale_arch:';

    protected $cacheTimeInMinutes = 1440; //缓存一天

    /**
     * @var int
     */
    protected $year;

    /**
     * @var int
     */
    protected $quarter;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var CacheManager
     */
    protected $cache;

    /**
     * BuildForecastOwnerArch constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = app(ClientInterface::class);
        $this->cache = app('cache');
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');
        $this->year = $this->option('year') ?: date('Y');
        $this->quarter = $this->option('quarter') ?: ceil(date('m') / 3);
        $this->info("Begin Building");
        $data = $this->getData($this->year, $this->quarter);
        $processedData = $this->processData($data, $this->buildSalesArch());
        unset($data);
        $this->saveData($processedData);
        unset($processedData);
        $this->info("Finish Building");
    }

    /**
     * 获取商机数据
     * @param $year
     * @param $quarter
     * @return Collection
     */
    protected function getData($year, $quarter)
    {
        /** @var Connection $connection */
        $connection = DB::connection('crm_brand');
        $data = $connection->table('t_opp_forecast AS oppf')
                    ->join('t_opp AS opp', 'oppf.Fopportunity_id', '=', 'opp.Fopportunity_id')
                    ->where([
                        ['oppf.Fyear', '=', $year],
                        ['oppf.Fq', '=', $quarter],
                        ['oppf.Fis_del', '=', OpportunityDef::NOT_DELETED],
                        ['oppf.Fopportunity_id', '!=', ''],
                        ['oppf.Fforecast_money', '>', 0],
                        ['opp.Fis_del', '=', OpportunityDef::NOT_DELETED],
                    ])->select([
                        'opp.Fopportunity_id AS opp_id',
                        'opp.Fopp_code AS opp_code',
                        'oppf.Fforecast_id AS forecast_id',
                        'opp.Fclient_id AS client_id',
                        'opp.Fagent_id AS agent_id',
                        'opp.Fbrand_id AS brand_id',
                        'opp.Fbelong_to AS belong_to',
                        'opp.Fowner_rtx AS owner_rtx',
                        'opp.Fstep AS step',
                        'opp.Fstate AS state',
                        'oppf.Fyear AS year',
                        'oppf.Fq AS quarter',
                        'oppf.Fforecast_money AS forecast_money',
                        'oppf.Forder_money AS order_money',
                        'oppf.Fforecast_money_remain AS forecast_money_remain',
                        'oppf.Fvideo_forecast_money AS video_forecast_money',
                        'oppf.Fvideo_order_money AS video_order_money',
                        'oppf.Fvideo_forecast_money_remain AS video_forecast_money_remain',
                        'oppf.Fnews_forecast_money AS news_forecast_money',
                        'oppf.Fnews_order_money AS news_order_money',
                        'oppf.Fnews_forecast_money_remain AS news_forecast_money_remain',
                    ])->get();
        $this->info("Fetch Data Count: " . $data->count());
        return $data;
    }

    /**
     * @param Collection $data
     * @param array $saleArch
     * @return Collection
     */
    protected function processData(Collection $data, array $saleArch)
    {
        $prefixConfig = [
            OpportunityDef::PRODUCT_TYPE_ALL => '',
            OpportunityDef::PRODUCT_TYPE_VIDEO => 'video_',
            OpportunityDef::PRODUCT_TYPE_NEWS => 'news_',
        ];
        $processedData = collect();
        foreach ($data as $item) {
            $ownerRtx = $item->owner_rtx;
            $baseInfo = [
                'opportunity_id' => $item->opp_id,
                'opportunity_code' => $item->opp_code,
                'forecast_id' => $item->forecast_id,
                'client_id' => $item->client_id,
                'agent_id' => $item->agent_id,
                'brand_id' => $item->brand_id,
                'year' => $item->year,
                'quarter' => $item->quarter,
                'sale_type' => $item->belong_to,
                'sale_id' => $saleArch[$ownerRtx]['sale_id'] ?? ArchitectDef::UNKNOWN_SALE_ID,
                'team_id' => $saleArch[$ownerRtx]['team_id'] ?? ArchitectDef::UNKNOWN_TEAM_ID,
                'centre_id' => $saleArch[$ownerRtx]['centre_id'] ?? ArchitectDef::UNKNOWN_CENTRE_ID,
                'area_id' => $saleArch[$ownerRtx]['area_id'] ?? ArchitectDef::UNKNOWN_AREA_ID,
                'department_id' => $saleArch[$ownerRtx]['department_id'] ?? ArchitectDef::UNKNOWN_DEPARTMENT_ID,
            ];
            $step = $item->step;
            $state = $item->state;
            foreach ($prefixConfig as $productType => $prefix) {
                $wip_money = $ongoing_money = 0;
                $forecast_money = $item->{$prefix . 'forecast_money'};
                $order_money = $item->{$prefix . 'order_money'};
                $forecast_money_remain = $item->{$prefix . 'forecast_money_remain'};
                if (empty($forecast_money) and empty($order_money)) {
                    continue;
                }
                if ((($step == OpportunityDef::STEP_WIP) || ($step == OpportunityDef::STEP_WIN)) && ($state == OpportunityDef::STATE_ONGOING)) {
                    $wip_money = max(0, $forecast_money - $order_money);
                }
                if ($step == OpportunityDef::STEP_ON_GOING) {
                    $ongoing_money = $forecast_money;
                }
                $tmp = [
                    'product_type' => $productType,
                    'forecast_money' => $forecast_money,
                    'wip_money' => $wip_money,
                    'ongoing_money' => $ongoing_money,
                    'order_money' => $order_money,
                    'remain_money' => $forecast_money_remain,
                ];
                $processedData->push($baseInfo + $tmp);
            }
        }
        return $processedData;
    }

    /**
     * @param Collection $data
     * @return void
     * @throws \Exception
     */
    protected function saveData(Collection $data)
    {
        $this->info("Save Data Count: " . $data->count());
        /** @var Connection $connection */
        $connection = DB::connection('opportunity');
        $connection->disableQueryLog();
        $table = $connection->table($this->storageTableName);
        $startTime = time();
        $connection->beginTransaction();
        try {
            $table->where('year', $this->year)->where('quarter', $this->quarter)->delete();
            $store = new MysqlStore($connection, $this->storageTableName);
            $store->save($data->toArray());
            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            $this->error("DB Transaction Error: " . $throwable->getMessage());
        }
        $this->info("Took " . (time() - $startTime) . " Seconds");
    }

    /**
     * 构架销售组织架构
     * @return array
     */
    protected function buildSalesArch()
    {
        $result = [];
        $this->info("Building Sales Arch");
        $startTime = time();
        $date = $this->getEndOfQuarterDate();
        $sales = $this->getAllSalesInfo($date, $date);
        foreach ($sales as $sale) {
            $rtx = $sale['rtx'];
            $result[$rtx]['sale_id'] = $sale['sale_id'];
            $teamInfo = $this->getSaleSuperiorsInfo($sale['sale_id'], $date, $date);
            foreach ($teamInfo as $team) {
                if ($team['level'] != ArchitectDef::TYPE_TEAM) {
                    continue;
                }
                $result[$rtx]['team_id'] = $team['team_id'];
                $teamSuperiors = $this->getTeamSuperiorsInfo($team['team_id'], $date);
                $teamSuperiors = current($teamSuperiors)['parents'];
                $result[$rtx]['centre_id'] = $teamSuperiors[ArchitectDef::TYPE_CENTRE]['Fteam_id'] ?? ArchitectDef::UNKNOWN_CENTRE_ID;
                $result[$rtx]['area_id'] = $teamSuperiors[ArchitectDef::TYPE_AREA]['Fteam_id'] ?? ArchitectDef::UNKNOWN_AREA_ID;
                $result[$rtx]['department_id'] = $teamSuperiors[ArchitectDef::TYPE_DEPARTMENT]['Fteam_id'] ?? ArchitectDef::UNKNOWN_DEPARTMENT_ID;
            }
        }
        $this->info("Took " . (time() - $startTime) . " Seconds");
        $this->info("Finish Building Sales Arch");
        return $result;
    }

    /**
     * 获取本季度最后一天日期
     * @return Carbon
     */
    protected function getEndOfQuarterDate()
    {
        $year = $this->year;
        $month = $this->quarter * 3;
        $day = 1;
        $timeString = "{$year}-{$month}-{$day}";
        return (new Carbon($timeString))->endOfQuarter();
    }

    /**
     * 获取所有销售信息
     * @param Carbon $begin
     * @param Carbon $end
     * @return array
     */
    protected function getAllSalesInfo(Carbon $begin, Carbon $end)
    {
        $url = 'http://archi.service/v1/sales';
        $params = [
            'page' => 1,
            'per_page' => 2000,
            'begin_date' => $begin->toDateString(),
            'end_date' => $end->toDateString(),
        ];
        return $this->getRequestData('GET', $url, $params);
    }

    /**
     * 获取销售的直属上级小组信息
     * @param $saleId
     * @param Carbon $begin
     * @param Carbon $end
     * @return array
     */
    protected function getSaleSuperiorsInfo($saleId, Carbon $begin, Carbon $end)
    {
        $url = "http://archi.service/v1/sales/{$saleId}/superiors";
        $params = [
            'begin_date' => $begin->toDateString(),
            'end_date' => $end->toDateString(),
        ];
        return $this->getRequestData('GET', $url, $params);
    }

    /**
     * 获取小组的所有上级信息(一直到Dept.)
     * @param $teamId
     * @param Carbon $date
     * @return array
     */
    protected function getTeamSuperiorsInfo($teamId, Carbon $date)
    {
        $url = "http://archi.service/v1/teams/{$teamId}/superiors";
        $params = [
            'end_date' => $date->toDateString(),
        ];
        return $this->getRequestData('GET', $url, $params);
    }

    /**
     * @param $method
     * @param $url
     * @param array $params
     * @return mixed
     */
    protected function getRequestData($method, $url , array $params)
    {
        $cacheKey = $this->getCacheKey($method, $url, $params);
        return $this->cache->remember($cacheKey, $this->cacheTimeInMinutes, function () use ($method, $url, $params) {
            $dataType = (strtoupper($method) == 'GET') ? 'query' : 'json';
            $response = $this->client->request($method, $url, [$dataType => $params]);
            $response = json_decode($response->getBody()->getContents(), true);
            return $response['data'];
        });
    }

    /**
     * @param $method
     * @param $url
     * @param array $params
     * @return string
     */
    protected function getCacheKey($method, $url, array $params)
    {
        return $this->cacheKeyPrefix . md5(json_encode([
                'method' => $method,
                'url' => $url,
                'params' => $params,
                ]));
    }
}
