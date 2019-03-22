<?php

namespace App\Services\Forecast;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\MicroService\ForecastClient;

/**
 * Class ArchitectService
 * @package App\Services\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class ForecastService
{
    public function getOverviewForecast($year, $quarter, $archType, $teamId, $channelType)
    {
        $forecastData = [];
        if (empty($year) || empty($quarter) || ProjectConst::SALE_CHANNEL_TYPE_CHANNEL == $channelType) {
            return $forecastData;
        }

        /**
         * @var ForecastClient $forecastService
         */
        $forecastService = app(ForecastClient::class);

        switch ($archType) {
            case ArchitectConstant::ARCHITECT_SYSTEM:
                $forecastData = $forecastService->getCountryForecast($year, $quarter);
                break;
            case ArchitectConstant::ARCHITECT_DEPT:
                $forecastData = $forecastService->getDepartmentForecast($year, $quarter, $teamId);
                break;
            case ArchitectConstant::ARCHITECT_AREA:
                $forecastData = $forecastService->getAreaForecast($year, $quarter, $teamId);
                break;
            case ArchitectConstant::ARCHITECT_DIRECTOR:
                $forecastData = $forecastService->getCentreForecast($year, $quarter, $teamId);
                break;
            case ArchitectConstant::ARCHITECT_LEADER:
                $forecastData = $forecastService->getTeamForecast($year, $quarter, $teamId);
                break;
            case ArchitectConstant::ARCHITECT_SALE:
            default:
                $forecastData = [];
                break;
        }
        return $forecastData[$teamId] ?? [];
    }

}
