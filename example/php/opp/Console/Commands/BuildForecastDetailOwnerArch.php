<?php

namespace App\Console\Commands;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use App\Services\ConstDef\OptionDef;
use App\Services\ConstDef\ArchitectDef;
use App\Services\ConstDef\OpportunityDef;

class BuildForecastDetailOwnerArch extends BuildForecastOwnerArch
{
    protected $signature = 'build:forecast-detail-owner-arch
                                {--year= : Build forecast details from which year}
                                {--quarter= : Build forecast details from which quarter}';

    protected $description = 'Build forecast detail owner architecture';

    protected $storageTableName = 't_opp_forecast_detail_owner_arch';

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
            ->join('t_opp_forecast_detail AS oppfd', 'oppf.Fforecast_id', '=', 'oppfd.Fforecast_id')
            ->where([
                ['oppf.Fyear', '=', $year],
                ['oppf.Fq', '=', $quarter],
                ['oppf.Fis_del', '=', OpportunityDef::NOT_DELETED],
                ['oppf.Fopportunity_id', '!=', ''],
                ['oppf.Fforecast_money', '>', 0],
                ['opp.Fis_del', '=', OpportunityDef::NOT_DELETED],
                ['oppfd.Fis_del', '=', OpportunityDef::NOT_DELETED],
            ])->whereIn('oppfd.Fcooperation_type', [OptionDef::COOPERATION_MERCHANT, OptionDef::COOPERATION_PRODUCT])
            ->select([
                'opp.Fopportunity_id AS opp_id',
                'opp.Fopp_code AS opp_code',
                'oppf.Fforecast_id AS forecast_id',
                'oppfd.Fforecast_detail_id AS forecast_detail_id',
                'opp.Fclient_id AS client_id',
                'opp.Fagent_id AS agent_id',
                'opp.Fbrand_id AS brand_id',
                'opp.Fbelong_to AS belong_to',
                'opp.Fowner_rtx AS owner_rtx',
                'opp.Fstep AS step',
                'opp.Fstate AS state',
                'oppf.Fyear AS year',
                'oppf.Fq AS quarter',
                'oppfd.Fplatform as platform',
                'oppfd.Fcooperation_type AS coop_type',
                'oppfd.Fbusiness_project_id AS project_id',
                'oppfd.Fbusiness_project AS project_name',
                'oppfd.Fresource_id AS resource_id',
                'oppfd.Fresource_name AS resource_name',
                'oppfd.Fad_product_id AS product_id',
                'oppfd.Fad_product AS product_name',
                'oppfd.Fplay_type_id AS play_type_id',
                'oppfd.Fplay_type AS play_type',
                'oppfd.Fforecast_money AS forecast_money',
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
        $processedData = collect();
        foreach ($data as $item) {
            $order_money = $wip_money = $ongoing_money = 0;
            $forecast_money = $item->forecast_money;
            $forecast_money_remain = $forecast_money - $order_money;
            $step = $item->step;
            $state = $item->state;
            if ((($step == OpportunityDef::STEP_WIP) || ($step == OpportunityDef::STEP_WIN)) && ($state == OpportunityDef::STATE_ONGOING)) {
                $wip_money = max(0, $forecast_money - $order_money);
            }
            if ($step == OpportunityDef::STEP_ON_GOING) {
                $ongoing_money = $forecast_money;
            }
            $ownerRtx = $item->owner_rtx;
            $baseInfo = [
                'opportunity_id' => $item->opp_id,
                'opportunity_code' => $item->opp_code,
                'forecast_id' => $item->forecast_id,
                'forecast_detail_id' => $item->forecast_detail_id,
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
                'product_type' => $this->getProductTypeByPlatform($item->platform),
                'coop_type' => $item->coop_type,
                'project_id' => $item->project_id,
                'project_name' => $item->project_name,
                'resource_id' => $item->resource_id,
                'resource_name' => $item->resource_name,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'play_type_id' => $item->play_type_id,
                'play_type' => $item->play_type,
                'forecast_money' => $forecast_money,
                'wip_money' => $wip_money,
                'ongoing_money' => $ongoing_money,
                'order_money' => $order_money,
                'remain_money' => $forecast_money_remain,
            ];
            $processedData->push($baseInfo);
        }
        return $processedData;
    }

    /**
     * @param $platform
     * @return integer
     */
    protected function getProductTypeByPlatform($platform)
    {
        if ($platform == OptionDef::PLATFORM_VIDEO) {
            return OpportunityDef::PRODUCT_TYPE_VIDEO;
        } elseif ($platform == OptionDef::PLATFORM_NEWS) {
            return OpportunityDef::PRODUCT_TYPE_NEWS;
        } else {
            return OpportunityDef::PRODUCT_TYPE_OTHER;
        }
    }
}
