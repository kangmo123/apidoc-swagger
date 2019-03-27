<?php

namespace App\Services\ConstDef;

class OpportunityDef
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 2000;

    // 商机来源
    const DATA_FROM_UNKNOW = 0; // 0-未知
    const DATA_FROM_CRM_S = 1; // 1-crm_s
    const DATA_FROM_CRM_NGS = 2; // 2-crm_ngs
    const DATA_FROM_WIN = 3; // 3-win
    const DATA_FROM_TWIN = 4; // 4-twin

    // 商机归属
    const BELONGTO_SALE = 1; // 商机归属 销售
    const BELONGTO_CHANNEL = 2; // 商机归属 渠道

    const BELONGTO_MAPS = [
        self::BELONGTO_SALE    => '销售',
        self::BELONGTO_CHANNEL => '渠道',
    ];

    // 商机是否共享
    const ISSHARE_NO = 0; // 商机是否共享 否
    const ISSHARE_YES = 1; // 商机是否共享 是

    const ISSHARE_MAPS = [
        self::ISSHARE_NO    => '否',
        self::ISSHARE_YES   => '是',
    ];

    // is_del 取值状态
    public const FIELD_DELETE = 'Fis_del';
    public const NOT_DELETED = 0;
    public const DELETED = 1;

    //商机金额单元跟业绩计算金额的换算比例
    public const OPP_REVENUE_MONEY_RATE = 1;

    // 商机阶段
    public const STEP_UNKNOWN = 0; // 未知
    public const STEP_ON_GOING = 2; // 商机阶段 跟进中
    public const STEP_WIP = 5; // 商机阶段 WIP
    public const STEP_WIN = 6; // 商机阶段 赢单
    public const STEP_LOSE = 7; // 商机阶段 失单
    public const STEP_MAPS = [
        self::STEP_UNKNOWN  => '未知',
        self::STEP_ON_GOING => '跟进中',
        self::STEP_WIP      => 'WIP',
        self::STEP_WIN      => '赢单',
        self::STEP_LOSE     => '失单',
    ];

    // 商机阶段/自评赢单概率之间映射关系
    public const STEP_PROBABILITY = [
        self::STEP_ON_GOING => 1,
        self::STEP_WIP => 61,
        self::STEP_WIN => 100,
        self::STEP_LOSE => 0,
    ];

    // 商机状态
    public const STATE_ONGOING = 1;
    public const STATE_CLOSED = 2;

    public const STATE_MAPS = [
        self::STATE_ONGOING => '进行中',
        self::STATE_CLOSED => '已关闭',
    ];

    // 商机归属
    public const BELONG_TO_SALE = 1; // 商机归属 销售
    public const BELONG_TO_CHANNEL = 2; // 商机归属 渠道

    //时间范围
    public const QTD_TYPE_Q = 'this_qtd';//本Q qtd
    public const QTD_TYPE_FQ = 'last_qtd';//上Q qtd
    public const QTD_TYPE_FY = 'last_year_qtd';//去年同期的Q qtd
    public const QUARTER_TYPE_Q = 'this_q'; //本Q整Q
    public const QUARTER_TYPE_FQ = 'last_q';//上Q整Q
    public const QUARTER_TYPE_FY = 'last_year_q'; //去年同期的Q整Q

    //组织架构层级
    public const ARCH_TYPE_NATION = 0; //全国
    public const ARCH_TYPE_AREA = 1; //片区
    public const ARCH_TYPE_DIRECTOR = 2; //总监
    public const ARCH_TYPE_TEAM = 3; //小组
    public const ARCH_TYPE_SALE = 4; //销售
    public const ARCH_TYPE_WHOLE = 10; // 品效整体

    //总监预估产品分类
    public const FORECAST_TYPE_ALL = 1;
    public const FORECAST_TYPE_VIDEO = 2;
    public const FORECAST_TYPE_NEWS = 4;

    //产品类型
    //!!!!此处的产品类型请不要随意修改，需要和业绩微服务保持一致
    public const PRODUCT_TYPE_ALL = 1;//品牌整体
    public const PRODUCT_TYPE_VIDEO = 2;//视频
    public const PRODUCT_TYPE_NEWS = 3;//新闻
    public const PRODUCT_TYPE_SNS_CONTRACT = 4;//朋友圈(合约)
    public const PRODUCT_TYPE_OTHER = 5;//品牌其他
    public const PRODUCT_TYPE_EFFECT_ALL = 6;//效果整体
    public const PRODUCT_TYPE_GDT = 7;//广点通
    public const PRODUCT_TYPE_MP = 8;//公众号
    public const PRODUCT_TYPE_SNS_BID = 9;//朋友圈(竞价)
    public const PRODUCT_TYPE_BRAND_EFFECT = 10;//品牌+效果的整体
    public const PRODUCT_TYPE_GDT_VIDEO = 11;//广点通-腾讯视频流量
    public const PRODUCT_TYPE_GDT_NEWS = 12;//广点通腾讯新闻
    public const PRODUCT_TYPE_GDT_KB = 13;//天天快报流量
    public const PRODUCT_TYPE_EFFECT_OTHERS = 14;//效果(其他)
    public const PRODUCT_TYPE_SNS_CONTRACT_EFFECT = 15;//朋友圈(合约)效果部分,全国业绩有用
    public const PRODUCT_TYPE_EXCEPT_BRAND_EFFECT = 20;//整体-品牌效果整体（其他）
    public const PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE = 21;//腾讯视频-非广告-不记业绩
    public const PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE = 22;//腾讯新闻-非广告-不记业绩
    public const PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE = 23;//其他-非广告-不记业绩
    public const PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER = 100;//品牌效果整体+其他
    //管理员架构id
    public const TOTAL_AREA_CODE = '00000000-0000-0000-0000-000000000008';

    public static $oppProductTypeMap = [
        OpportunityDef::PRODUCT_TYPE_VIDEO => 'video',
        OpportunityDef::PRODUCT_TYPE_NEWS  => 'news',
    ];
}
