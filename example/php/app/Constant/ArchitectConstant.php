<?php

namespace App\Constant;

/**
 * Class TaskConstant
 * @package App\Constant
 * @author caseycheng <caseycheng@tencent.com>
 */
class ArchitectConstant extends Constant
{
    /**
     * 品牌
     */
    const DIRECT_SALE_TEAM_GUID = 'F3CE6EE0-21DA-6C65-455B-01EB65690217';
    const DIRECT_LEADER_SALE_ID = "42916176-5FF3-DD11-A85D-001D096CF989";
    const DIRECT_TEAM_NAME = "直客";
    /**
     * ka渠道中心
     */
    const KA_CHANNEL_CENTER_TEAM_GUID = '0BD96219-41FE-11E8-8954-F02FA73BC80D';
    /**
     * 渠道业务部
     */
    const CHANNEL_BUSINESS_DEPARTMENT = '40B96BF7-30AA-11E8-A977-F02FA73BC80D';
    const CHANNEL_LEADER_SALE_ID = "42916176-5FF3-DD11-A85D-001D096CF989";
    const CHANNEL_TEAM_NAME = "渠道";

    const GROUP_DIRECT = "direct";
    const GROUP_CHANNEL = "channel";
    const GROUP_COMMENT_DIRECT = "直客";
    const GROUP_COMMENT_CHANNEL = "渠道";

    //组织架构层级
    const ARCHITECT_NONE = -1;
    const ARCHITECT_SYSTEM = 0;
    const ARCHITECT_AREA = 1;
    const ARCHITECT_DIRECTOR = 2;
    const ARCHITECT_LEADER = 3;
    const ARCHITECT_SALE = 4;
    const ARCHITECT_SHORT = 5;
    const ARCHITECT_ACCOUNT = 6;
    const ARCHITECT_AREA_ASSISTANT = 7;
    const ARCHITECT_DEPT = 8;

    //小组层级
    const TEAM_LEVEL_NONE = -1;
    const TEAM_LEVEL_CHANNEL = 0;
    const TEAM_LEVEL_DEPT = 1;
    const TEAM_LEVEL_AREA = 10;
    const TEAM_LEVEL_DIRECTOR = 20;
    const TEAM_LEVEL_LEADER = 30;

    //组织架构类型，1-销售，2-渠道,0-无类型
    const TEAM_TYPE_NONE = 0;
    const TEAM_TYPE_SALE = 1;
    const TEAM_TYPE_CHANNEL = 2;

    const NODE_TYPE_TEAM = 0;
    const NODE_TYPE_SALE = 1;
    public static $smbDirectAreas = [
        'F0C30C22-F37E-AA6F-8572-8C31A06B6F2F',//行业拓展中心
        '7D36EFBD-6010-7F99-348A-7ED987F9AFC8', //电销业务组
    ];
    public static $smbChannelAreas = [
        '9C6D1AAF-701A-8FCD-B242-77D9E8C5EE55', //区域和渠道中心
    ];
}