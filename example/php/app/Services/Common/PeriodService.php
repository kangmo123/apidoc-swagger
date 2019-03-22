<?php

namespace App\Services\Common;


use App\Constant\TaskConstant;
use Carbon\Carbon;

class PeriodService
{

    /**
     * @var ConfigService
     */
    protected $configService = null;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @return array
     */
    public function getPeriods()
    {
        $today = Carbon::create()->startOfDay();
        $diffDay = $this->getNextTaskAssignDay();
        $periods = [];
        if ($today >= $diffDay) {
            //上Q，本Q，下Q
            $nextQuarterDay = Carbon::create()->addQuarter();
            $nextQuarter = sprintf("%sQ%s", $nextQuarterDay->year, $nextQuarterDay->quarter);
            $periods[] = $nextQuarter;
        }
        $periods[] = sprintf("%sQ%s", $today->year, $today->quarter);
        $previousQuarterDay = $today->subQuarter();
        $periods[] = sprintf("%sQ%s", $previousQuarterDay->year, $previousQuarterDay->quarter);
        return $periods;
    }

    /**
     * @return Carbon
     */
    protected function getNextTaskAssignDay()
    {
        $key = TaskConstant::CONFIG_DIFF_DAYS_NEXT_QUARTER_OF_TASK;
        $config = $this->configService->getConfig($key);
        $value = isset($config[$key]) ? (int)$config[$key] : TaskConstant::DEFAULT_DIFF_DAYS;
        $diffDay = Carbon::create()->endOfQuarter()->subDays($value)->startOfDay();
        return $diffDay;
    }


}