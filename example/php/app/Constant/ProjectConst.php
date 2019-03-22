<?php

namespace App\Constant;


class ProjectConst
{
    const SALE_CHANNEL_TYPE_DIRECT = "direct";
    const SALE_CHANNEL_TYPE_CHANNEL = "channel";
    const MAX_RATE = 10000;
    //单位
    const UNIT = 1000;

    public static $productTypeTaskColumnMap = [
        RevenueConst::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => 'union',
        RevenueConst::PRODUCT_TYPE_BRAND_EFFECT => 'total',
        RevenueConst::PRODUCT_TYPE_ALL => 'brand',
        RevenueConst::PRODUCT_TYPE_EFFECT_ALL => 'effect',
        RevenueConst::PRODUCT_TYPE_VIDEO => 'video',
        RevenueConst::PRODUCT_TYPE_NEWS => 'news',
        RevenueConst::PRODUCT_TYPE_OTHER => 'other'
    ];

    // 客户下单: 图表相关取得维度 (日后依照性质搬迁至合适的常量文档中)
    const CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI = "product_archi"; // 产品结构
    const CLIENT_ORDER_DIMENSION_SELL_METHOD = "sell_method"; // 售卖方式
    
    const DEFAULT_PAGE_SIZE = 10;
    const DEFAULT_PAGE = 1;
}
