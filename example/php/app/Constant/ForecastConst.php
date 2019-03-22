<?php

namespace App\Services\ConstDef;

/**
 * Class RuleDef
 * @package console\components\dictionary
 */
class ForecastConst
{
    const FORECAST_TYPE_ALL = 1;
    const FORECAST_TYPE_VIDEO = 2;
    const FORECAST_TYPE_NEWS = 3;

    public static $forecastProductTypeMap = [
        self::FORECAST_TYPE_ALL => 'brand',
        self::FORECAST_TYPE_VIDEO => 'video',
        self::FORECAST_TYPE_NEWS => 'news'
    ];
}
