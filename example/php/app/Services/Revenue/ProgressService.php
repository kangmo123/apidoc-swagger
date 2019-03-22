<?php

namespace App\Services\Revenue;

use App\Constant\ArchitectConstant;
use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\Services\Forecast\ForecastService;
use App\Services\Revenue\Formatter\MobileOverallFormatter;
use App\Services\Revenue\Formatter\OverallFormatter;
use App\Services\Revenue\Formatter\ProgressOverallFormatter;
use App\Services\Revenue\Summary\ChannelSummaryService;
use App\Services\Revenue\Summary\DirectSummaryService;
use App\Utils\NumberUtil;
use App\Utils\Utils;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Class ArchitectService
 * @package App\Services\Revenue
 */
class ProgressService extends RevenueService
{
    protected $compareMap = [
        ArchitectConstant::ARCHITECT_DEPT =>
            [
                ArchitectConstant::ARCHITECT_SYSTEM
            ],
        ArchitectConstant::ARCHITECT_AREA =>
            [
                ArchitectConstant::ARCHITECT_DEPT,
                ArchitectConstant::ARCHITECT_SYSTEM
            ],
        ArchitectConstant::ARCHITECT_DIRECTOR =>
            [
                ArchitectConstant::ARCHITECT_AREA,
                ArchitectConstant::ARCHITECT_DEPT
            ],
        ArchitectConstant::ARCHITECT_LEADER =>
            [
                ArchitectConstant::ARCHITECT_DIRECTOR,
                ArchitectConstant::ARCHITECT_AREA
            ],
        ArchitectConstant::ARCHITECT_SALE =>
            [
                ArchitectConstant::ARCHITECT_LEADER,
                ArchitectConstant::ARCHITECT_DIRECTOR
            ],
    ];

    /**
     * @param $year
     * @param $quarter
     * @param $tree
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @param $channelType
     * @return array
     */
    public function getCompareData($year, $quarter, $tree, $archType, $saleId, $teamId, $channelType)
    {
        $formatData = [];

        if (ProjectConst::SALE_CHANNEL_TYPE_CHANNEL == $channelType) {
            $this->compareMap = [
                ArchitectConstant::ARCHITECT_AREA =>
                    [
                        ArchitectConstant::ARCHITECT_DEPT,
                    ],
                ArchitectConstant::ARCHITECT_DIRECTOR =>
                    [
                        ArchitectConstant::ARCHITECT_AREA,
                        ArchitectConstant::ARCHITECT_DEPT
                    ],
                ArchitectConstant::ARCHITECT_LEADER =>
                    [
                        ArchitectConstant::ARCHITECT_DIRECTOR,
                        ArchitectConstant::ARCHITECT_AREA
                    ],
                ArchitectConstant::ARCHITECT_SALE =>
                    [
                        ArchitectConstant::ARCHITECT_LEADER,
                        ArchitectConstant::ARCHITECT_DIRECTOR
                    ],
            ];
        }

        $architectData = $this->getArchitectData($year, $quarter, $archType, $saleId, $teamId, $channelType);
        /**
         * @var ProgressOverallFormatter $progressFormatter
         */
        $progressFormatter = app(ProgressOverallFormatter::class);

        foreach ($architectData as $value) {
            $overallData = $this->getFlattenSaleOverallDataQuarterly($year, $quarter, $value['arch_type'], $saleId,
                ArchitectConstant::ARCHITECT_SALE == $value['arch_type'] ? $value['pid'] : $value['id'],
                $channelType);
            list($originRevenueOppData, $taskData, $forecastData) = $overallData;
            $data = $progressFormatter->getCompareData($tree, $taskData, $forecastData, $originRevenueOppData);
            $data['name'] = $value['name'];
            $formatData[] = $data;
        }

        return $formatData;
    }

    /**
     * @param $year
     * @param $quarter
     * @param $archType
     * @param $saleId
     * @param $teamId
     * @param $channelType
     * @return array
     */
    protected function getArchitectData($year, $quarter, $archType, $saleId, $teamId, $channelType)
    {
        $data = [];
        if (empty($this->compareMap[$archType])) {
            return $data;
        }
        $data = [];
        $info = $tmp = $this->getArchitect($year, $quarter, $saleId, $teamId, $archType);
        $data[] = $tmp;
        foreach ($this->compareMap[$archType] as $level) {
            $info = $this->getArchitect($year, $quarter, $saleId, $info['pid'], $level);
            $data[] = $info;
        }
        return $data;
    }

    /**
     * @param $year
     * @param $quarter
     * @param $saleId
     * @param $teamId
     * @param $archType
     * @return array
     */
    private function getArchitect($year, $quarter, $saleId, $teamId, $archType)
    {
        $begin = Carbon::create($year, $quarter * 3, 1)->firstOfQuarter()->format("Y-m-d");
        $end = Carbon::create($year, $quarter * 3, 1)->endOfQuarter()->format("Y-m-d");
        /**
         * @var ArchitectClient $architectClient
         */
        $architectClient = app(ArchitectClient::class);
        $data = $architectClient->getTeamInfo($teamId, $begin, $end);
        $team = \current($data);
        if (ArchitectConstant::ARCHITECT_SALE == $archType) {
            /**
             * @var User $user
             */
            $user = Auth::user();
            return [
                'id' => $saleId,
                'pid' => $teamId,
                'name' => $user->getName(),
                'arch_type' => $archType,
            ];
        } elseif (ArchitectConstant::ARCHITECT_SYSTEM == $archType) {
            return [
                'id' => $teamId,
                'pid' => null,
                'name' => '全国',
                'arch_type' => $archType
            ];
        } else {
            return [
                'id' => $teamId,
                'pid' => $team['pid'],
                'name' => $team['name'],
                'arch_type' => $archType
            ];
        }
    }
}
