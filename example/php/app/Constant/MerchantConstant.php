<?php

namespace App\Constant;

/**
 * Class TaskConstant
 * @package App\Constant
 * @author caseycheng <caseycheng@tencent.com>
 */
class MerchantConstant extends Constant
{

    const PRI_MERCHANT_REVENUE_ADMIN = 'crm_merchant_revenue_admin';

    protected static $fieldDict = [
        'merchant_id' => 'merchant_code',
        'qtd_money' => 'cost',
        'q_opp_ongoing' => 'q_ongoing',
    ];

    public static $productDict = [
        RevenueConst::PRODUCT_TYPE_VIDEO => '腾讯视频',
        RevenueConst::PRODUCT_TYPE_NEWS => '新闻客户端',
        RevenueConst::PRODUCT_TYPE_OTHER => '其他',
    ];

    public static function convertField($name)
    {
        if (array_key_exists($name, self::$fieldDict)) {
            return self::$fieldDict[$name];
        }
        return $name;
    }

    public static function revertField($name)
    {
        $dict = array_flip(self::$fieldDict);
        if (array_key_exists($name, $dict)) {
            return $dict[$name];
        }
        return $name;
    }
}