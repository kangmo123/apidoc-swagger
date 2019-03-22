<?php
/**
 * 业绩相关的字典的文件
 */

namespace App\Constant;

class RevenueConst
{
    //2017年Q3，Q4片区架构
    public static $brandArea = [
        'KA1' => 'KA第一业务群组',
        'KA2' => 'KA第二业务群组',
        'KA3' => 'KA第三业务群组',
        'KA4' => 'KA一组',
        'KAEZ' => 'KA二组',
        'LSZ' => '零售组',
        'KA9' => 'KA九组',
        'FMCG' => '快销销售中心',
        'JT' => '汽车销售中心',
        'FIT' => '消电IT金融销售中心',
        'WY' => '网服网游销售中心'
    ];

    //产品类型
    //!!!!此处的产品类型请不要随意修改，需要和业绩微服务保持一致
    const PRODUCT_TYPE_ALL = 1;//品牌整体
    const PRODUCT_TYPE_VIDEO = 2;//视频
    const PRODUCT_TYPE_NEWS = 3;//新闻
    const PRODUCT_TYPE_SNS_CONTRACT = 4;//朋友圈(合约)
    const PRODUCT_TYPE_OTHER = 5;//品牌其他
    const PRODUCT_TYPE_EFFECT_ALL = 6;//效果整体
    const PRODUCT_TYPE_GDT = 7;//广点通
    const PRODUCT_TYPE_MP = 8;//公众号
    const PRODUCT_TYPE_SNS_BID = 9;//朋友圈(竞价)
    const PRODUCT_TYPE_BRAND_EFFECT = 10;//品牌+效果的整体
    const PRODUCT_TYPE_GDT_VIDEO = 11;//广点通-腾讯视频流量
    const PRODUCT_TYPE_GDT_NEWS = 12;//广点通腾讯新闻
    const PRODUCT_TYPE_GDT_KB = 13;//天天快报流量
    const PRODUCT_TYPE_EFFECT_OTHERS = 14;//效果(其他)
    const PRODUCT_TYPE_EXCEPT_BRAND_EFFECT = 20;//整体-品牌效果整体（其他）
    const PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE = 21;//腾讯视频-非广告-不记业绩
    const PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE = 22;//腾讯新闻-非广告-不记业绩
    const PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE = 23;//其他-非广告-不记业绩
    const PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER = 100;//品牌效果整体+其他

    //移动端才使用的常规、招商
    const MOBILE_PRODUCT_TYPE_VIDEO_NORMAL = 2001;
    const MOBILE_PRODUCT_TYPE_VIDEO_BUSINESS = 2002;
    const MOBILE_PRODUCT_TYPE_NEWS_NORMAL = 3001;
    const MOBILE_PRODUCT_TYPE_NEWS_BUSINESS = 3002;


    //常规收入
    const INCOME_TYPE_CG = 1;
    const INCOME_TYPE_CG_NAME = '常规';
    const INCOME_TYPE_CG_M = 'normal';

    //招商收入
    const INCOME_TYPE_ZS = 2;
    const INCOME_TYPE_ZS_NAME = '招商';
    const INCOME_TYPE_ZS_M = 'business';

    static $incomeTypeNameMap = [
        self::INCOME_TYPE_CG => self::INCOME_TYPE_CG_NAME,
        self::INCOME_TYPE_ZS => self::INCOME_TYPE_ZS_NAME
    ];

    const TREE_BRAND_EXPAND_TYPE = 'brand';

    //组织架构层级
    const ARCH_TYPE_NATION = 0; //全国
    const ARCH_TYPE_AREA = 1; //片区
    const ARCH_TYPE_DIRECTOR = 2; //总监
    const ARCH_TYPE_TEAM = 3; //小组
    const ARCH_TYPE_SALE = 4; //销售
    const ARCH_TYPE_SHORT = 5; //简称
    const ARCH_TYPE_CLIENT = 6; //客户
    const ARCH_TYPE_DEPT = 8; //部门
    const ARCH_TYPE_WHOLE = 10; // 品效整体

    public static $productTypeMergeTree = [
        //整体收入
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => [
            //品牌+效果的整体
            self::PRODUCT_TYPE_BRAND_EFFECT => [
                //品牌
                self::PRODUCT_TYPE_ALL => [
                    self::PRODUCT_TYPE_VIDEO,
                    self::PRODUCT_TYPE_NEWS,
                    self::PRODUCT_TYPE_SNS_CONTRACT,
                    self::PRODUCT_TYPE_OTHER
                ],
                //效果
                self::PRODUCT_TYPE_EFFECT_ALL => [
                    self::PRODUCT_TYPE_GDT,
                    self::PRODUCT_TYPE_MP,
                    self::PRODUCT_TYPE_SNS_BID
                ]
            ],
            //非品牌+效果的其它
            self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => [
                self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE,
                self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE,
                self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE
            ]
        ]
    ];

    /**
     * 业绩概览树结构
     * @var array
     */
    public static $revenueOverallTree = [
        /**
         * 品牌+效果整体+其他
         */
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => [
            'children' => [
                //品效整体
                self::PRODUCT_TYPE_BRAND_EFFECT => [
                    'children' => [
                        //品牌整体
                        self::PRODUCT_TYPE_ALL => [
                            'leaf_nodes_list' => [
                                [
                                    'node' => self::PRODUCT_TYPE_VIDEO,
                                    'expand_type' => self::TREE_BRAND_EXPAND_TYPE
                                ],
                                [
                                    'node' => self::PRODUCT_TYPE_NEWS,
                                    'expand_type' => self::TREE_BRAND_EXPAND_TYPE
                                ],
                                [
                                    'node' => self::PRODUCT_TYPE_SNS_CONTRACT,
                                    'expand_type' => self::TREE_BRAND_EXPAND_TYPE
                                ],
                                [
                                    'node' => self::PRODUCT_TYPE_OTHER,
                                    'expand_type' => self::TREE_BRAND_EXPAND_TYPE
                                ]
                            ]
                        ],
                        //效果整体
                        self::PRODUCT_TYPE_EFFECT_ALL => [
                            'leaf_nodes_list' => [
                                [
                                    'node' => self::PRODUCT_TYPE_GDT
                                ],
                                [
                                    'node' => self::PRODUCT_TYPE_MP
                                ],
                                [
                                    'node' => self::PRODUCT_TYPE_SNS_BID
                                ]
                            ]
                        ]
                    ]
                ],
                //其他
                self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => [
                    'leaf_nodes_list' => [
                        //腾讯视频-非广告-不记业绩
                        [
                            'node' => self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE
                        ],
                        //腾讯新闻-非广告-不记业绩
                        [
                            'node' => self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE
                        ],
                        //其他-非广告-不记业绩]
                        [
                            'node' => self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * 产品id、名称映射表
     * @var array
     */
    public static $productTypeNameMap = [
        self::PRODUCT_TYPE_ALL => "品牌",
        self::PRODUCT_TYPE_VIDEO => "腾讯视频",
        self::PRODUCT_TYPE_NEWS => "新闻APP",
        self::PRODUCT_TYPE_SNS_CONTRACT => "合约朋友圈",
        self::PRODUCT_TYPE_OTHER => "其他品牌收入",
        self::PRODUCT_TYPE_EFFECT_ALL => "效果",
        self::PRODUCT_TYPE_GDT => "广点通",
        self::PRODUCT_TYPE_MP => "公众号",
        self::PRODUCT_TYPE_SNS_BID => "竞价朋友圈",
        self::PRODUCT_TYPE_BRAND_EFFECT => "品效整体",
        self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE => "腾讯视频-非广告-不计平台业绩",
        self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE => "腾讯新闻-非广告-不计平台业绩",
        self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE => "其他非广告收入-不计平台业绩",
        self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => "其他",
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => "整体"
    ];
    
    public static $productTypeEnglishNameMap = [
        self::PRODUCT_TYPE_ALL => "all",
        self::PRODUCT_TYPE_VIDEO => "video",
        self::PRODUCT_TYPE_NEWS => "news",
        self::PRODUCT_TYPE_SNS_CONTRACT => "sns_contract",
        self::PRODUCT_TYPE_OTHER => "other",
        self::PRODUCT_TYPE_EFFECT_ALL => "effect_all",
        self::PRODUCT_TYPE_GDT => "gdt",
        self::PRODUCT_TYPE_MP => "mp",
        self::PRODUCT_TYPE_SNS_BID => "sns_bid",
        self::PRODUCT_TYPE_BRAND_EFFECT => "brand_effect",
        self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE => "video_non_ad_no_revenue",
        self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE => "news_non_ad_no_revenue",
        self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE => "others_non_ad_no_revenue",
        self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => "except_brand_effect",
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => "brand_effect_and_other"
    ];
    
    // 下单趋势的指定产品排序：当前注重效果中的 01-05 ，其他随意
    public static $productTypeSortMap = [
        self::PRODUCT_TYPE_ALL => "11",
        self::PRODUCT_TYPE_VIDEO => "01",
        self::PRODUCT_TYPE_NEWS => "02",
        self::PRODUCT_TYPE_SNS_CONTRACT => "03",
        self::PRODUCT_TYPE_OTHER => "04",
        self::PRODUCT_TYPE_EFFECT_ALL => "05",
        self::PRODUCT_TYPE_GDT => "21",
        self::PRODUCT_TYPE_MP => "22",
        self::PRODUCT_TYPE_SNS_BID => "23",
        self::PRODUCT_TYPE_BRAND_EFFECT => "24",
        self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE => "25",
        self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE => "26",
        self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE => "27",
        self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => "28",
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => "29"
    ];
    
    //admin权限
    const PRI_OPERATOR_DIRECT = "crm_dashboard_direct";     //直客运营
    const PRI_OPERATOR_CHANNEL = "crm_dashboard_channel";   //渠道行业运营

    //业绩微服务公共接口字段
    public static $revenueApiCommonField = [
        'qtd_money',
        'qtd_money_fq',
        'qtd_money_fy',
        'q_money_fq',
        'q_money_fy',
        'qtd_normal_money',
        'qtd_business_money',
    ];

    const REVENUE_DEFAULT_STR = "";

    /**
     * 资源类型、任务对应字段map
     * @var array
     */
    public static $productTypeTaskColumnMap = [
        self::PRODUCT_TYPE_BRAND_EFFECT_AND_OTHER => 'union',
        self::PRODUCT_TYPE_BRAND_EFFECT => 'total',
        self::PRODUCT_TYPE_ALL => 'brand',
        self::PRODUCT_TYPE_EFFECT_ALL => 'effect',
        self::PRODUCT_TYPE_VIDEO => 'video',
        self::PRODUCT_TYPE_NEWS => 'news',
        self::PRODUCT_TYPE_OTHER => 'other'
    ];

    //业绩片区指定排序规则
    public static $validAreaList = [
        'KA一组' => 1,
        'KA二组' => 2,
        'KA五组' => 3,
        'KA九组' => 4,
        'KA第一业务群组' => 5,
        'KA第二业务群组' => 6,
        'KA第三业务群组' => 7,
        '零售组' => 8,
        '快消销售中心' => 9,
        '汽车销售中心' => 10,
        '消电IT金融销售中心' => 11,
        '网服网游销售中心' => 12,
        '国内代理业务发展中心' => 13,
        '国际代理业务发展中心' => 14,
    ];

    public static $validNewAreaList = [
        '零售品效联动行业中心' => 1,
        '消费品行业中心' => 2,
        '汽车行业中心' => 3,
        '消电房产医药行业中心' => 4,
        '大客户与生态平台中心' => 5,
        '电商行业中心' => 6,
        '销售中心' => 7,
        '游戏行业中心' => 8,
        '综合网服行业中心' => 9,
        '行业拓展中心' => 10,
        '电销业务组' => 11,
        '区域和渠道中心' => 12,
        '国内代理业务发展中心' => 13,
        '国际代理业务发展中心' => 14,
        '渠道管理赋能组' => 15,
    ];

    /**
     * 用于排除客户、简称层级，本Q未下单且上Q、去年同Q、商机等都很小的客户
     * 任何值大于等于其最小值都会返回给前端显示
     *
     * @var $filterNoQtdMoneyClients array
     */
    public static $filterNoQtdMoneyClients = [
        'qtd_money' => 0, //本Q已下单最小值
        'q_money_fq' => 0, //上Q下单最小值
        'q_money_fy' => 0, //
        'q_opp' => 0
    ];

    //客户和简称层级业绩汇总时需要相加的字段
    public static $clientsShortsRevenueFieldsToAdd = [
        'qtd_money',
        'qtd_money_fq',
        'qtd_money_fy',
        'q_money_fq',
        'q_money_fy',
        'q_wip',
        'q_wip_fy',
        'q_opp',
        'q_opp_fy',
        'qtd_normal_money',
        'qtd_normal_money_fy',
        'qtd_business_money',
        'qtd_business_money_fy',
        'q_forecast',
    ];

    /**
     * @var array 移动端的业绩树格式(业绩移动端用的（非计算过程）)
     */
    public static $mobileRevenueOverallTree = [
        [
            'node' => self::PRODUCT_TYPE_VIDEO,
            'expand_type' => self::TREE_BRAND_EXPAND_TYPE
        ],
        [
            'node' => self::PRODUCT_TYPE_NEWS,
            'expand_type' => self::TREE_BRAND_EXPAND_TYPE
        ],
        [
            'node' => self::PRODUCT_TYPE_SNS_CONTRACT,
            'expand_type' => self::TREE_BRAND_EXPAND_TYPE
        ],
        [
            'node' => self::PRODUCT_TYPE_OTHER,
            'expand_type' => self::TREE_BRAND_EXPAND_TYPE
        ],
        self::PRODUCT_TYPE_EFFECT_ALL => [
            self::PRODUCT_TYPE_GDT,
            self::PRODUCT_TYPE_MP,
            self::PRODUCT_TYPE_SNS_BID
        ],
        self::PRODUCT_TYPE_EXCEPT_BRAND_EFFECT => [
            //腾讯视频-非广告-不记业绩
            self::PRODUCT_TYPE_VIDEO_NON_AD_NO_REVENUE,
            //腾讯新闻-非广告-不记业绩
            self::PRODUCT_TYPE_NEWS_NON_AD_NO_REVENUE,
            //其他-非广告-不记业绩]
            self::PRODUCT_TYPE_OTHERS_NON_AD_NO_REVENUE
        ],
    ];

    public static $departments = [
        ProjectConst::SALE_CHANNEL_TYPE_DIRECT => [
            '6CC5BE11-FBAF-F34D-CA01-60A70FE4E400', //ka业务部
            'C97F0221-7FB2-4C78-4B5B-A12D2F2CE27C'  //行业业务部
        ],
        ProjectConst::SALE_CHANNEL_TYPE_CHANNEL => [
            '40B96BF7-30AA-11E8-A977-F02FA73BC80D'  //渠道业务部
        ]
    ];

    public static $newDepartments = [
        ProjectConst::SALE_CHANNEL_TYPE_DIRECT => [
            '1BA84FCE-2215-5E82-9089-F7677C126D8E', //行业销售运营一部
            '20B400D9-A932-275E-0048-0C9794BFFE7A', //行业销售运营二部
            'C658699F-EC2B-A942-588E-C177CDA408AA', //区域及中长尾业务部
        ],
        ProjectConst::SALE_CHANNEL_TYPE_CHANNEL => [
            '6CFE126F-73DB-5E8C-DE1A-6AA92AA84B83', //渠道管理部
            'C658699F-EC2B-A942-588E-C177CDA408AA', //区域及中长尾业务部
        ]
    ];

    const TIME_RANGE_TYPE_DAILY = "daily";
    const TIME_RANGE_TYPE_WEEKLY = "weekly";
    const TIME_RANGE_TYPE_MONTHLY = "monthly";
    const TIME_RANGE_TYPE_QUARTERLY = "quarterly";

    const TREND_DATA_TYPE_VALUE = "value";
    const TREND_DATA_TYPE_RATIO = "ratio";

    const DOWNLOAD_MONEY_UNIT_NAME = "千元";
    const DOWNLOAD_MONEY_UNIT_RATIO = 1000;

    public static $teamLevelToArchType = [
        1 => RevenueConst::ARCH_TYPE_DEPT,
        10 => RevenueConst::ARCH_TYPE_AREA,
        20 => RevenueConst::ARCH_TYPE_DIRECTOR,
        30 => RevenueConst::ARCH_TYPE_TEAM,
    ];
}
