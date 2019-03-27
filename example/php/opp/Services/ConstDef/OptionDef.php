<?php

namespace App\Services\ConstDef;

class OptionDef
{
    public const DEFAULT_PER_PAGE = 20;
    public const DEFAULT_PAGE = 1;

    // 配置项 TYPE 取值
    public const OPTION_TYPE_DEFAULT = 1; // 默认取值

    public const OPTION_INV_PLATFORMS = 1; // 投放平台
    public const OPTION_COO_FORMS = 2; // 合作形式
    public const OPTION_INV_PROJECTS = 3; // 招商项目
    public const OPTION_AD_PRODUCTS = 4; // 专项产品
    public const OPTION_FORMS = 5; // 播放形式
    public const OPTION_SEATS = 6; // 招商合作内容
    const OPTIONS_MAP = [
        self::OPTION_INV_PLATFORMS => '投放平台',
        self::OPTION_COO_FORMS     => '合作形式',
        self::OPTION_INV_PROJECTS  => '招商项目',
        self::OPTION_AD_PRODUCTS   => '广告产品',
        self::OPTION_FORMS         => '播放形式',
        self::OPTION_SEATS         => '席位名',
    ];

    // 投放平台
    public const PLATFORM_VIDEO = 101; // 101-腾讯视频
    public const PLATFORM_NEWS = 102; // 102-新闻资讯
    public const PLATFORM_BG = 103; // 103-外bg
    public const PLATFORM_CROSS = 110; // 110-跨平台
    public const PLATFORM_OTHER = 5555; // 其他
    public const PLATFORM_SPECIAL = 9999; // 特殊项目

    // 合作形式
    public const COOPERATION_MERCHANT = 1; // 1-招商项目
    public const COOPERATION_PRODUCT = 2; // 2-专项产品
    public const COOPERATION_OTHER = 3; // 3-其他
}
