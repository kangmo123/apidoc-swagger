<?php

namespace App\Constant;

/**
 * Class TaskConstant
 * @package App\Constant
 * @author caseycheng <caseycheng@tencent.com>
 */
class TaskConstant extends Constant
{

    const TYPE_TOTAL = 0;           //运营指定的总任务
    const TYPE_ARCHITECT = 1;       //正常组织架构的任务

    const TASK_STATUS_UNASSIGNED = 0;
    const TASK_STATUS_ASSIGNED = 1;
    const TASK_STATUS_LOCKED = 2;

    //配置表中，离下Q还有N天的时候，开始进行任务分配
    const CONFIG_DIFF_DAYS_NEXT_QUARTER_OF_TASK = "diff_days_next_quarter_of_task";
    const CONFIG_ARCHITECTS_CHANNELS = "architects_channels";
    const DEFAULT_DIFF_DAYS = 15;       //默认下Q开始15天之前进行任务分配

    //admin权限
    const PRI_OPERATOR_IND1 = "crm_task_op_industry1";        //行业一部任务运营
    const PRI_OPERATOR_IND2 = "crm_task_op_industry2";        //行业二部任务运营
    const PRI_OPERATOR_SMB = "crm_task_op_smb";               //SMB任务运营
    const PRI_OPERATOR_CHANNEL = "crm_task_op_channel";       //渠道任务运营
    const PRI_OPERATOR_DIRECT = "crm_task_op_direct";         //直客运营

    const PRI_TASK_ADMIN = "crm_task_admin";                        //任务管理
    const PRI_TASK_MENU = "crm_task_menu";
    const PRI_TASK_QUERY = "crm_task_query";
    const PRI_TASK_ASSIGN = "crm_task_assign";

    const PRI_TASK_TOTAL_TASK = "crm_task_total_task";              //制定总任务的权限、即有这个权限的人是运营
    const PRI_TASK_QUERY_TASK = "crm_task_query";                   //任务查询
    const PRI_TASK_UPLOAD_TASK = "crm_task_upload_task";

    //系统访问policy
    const POLICY_TASK_QUERY = "task.query";
    const POLICY_TASK_UPLOAD = "task.upload";

    public static $operatorPrivileges = [
        self::PRI_OPERATOR_IND1,
        self::PRI_OPERATOR_IND2,
        self::PRI_OPERATOR_SMB,
        self::PRI_OPERATOR_CHANNEL,
    ];

    const LEVEL_NATION = 0;
    const LEVEL_DEPT = 10;
    const LEVEL_AREA = 20;
    const LEVEL_CENTER = 30;
    const LEVEL_TEAM = 40;
    const LEVEL_SALE = 50;

    /**
     * 任务维度中英文对照
     * @var array
     */
    public static $taskDetailDict = [
        "total" => "整体任务",
        "video" => "视频任务",
        "news" => "新闻任务",
        "brand" => "品牌任务",
        "performance" => "效果任务",
    ];

    public static $productDict = [
        1 => "brand",
        2 => "video",
        3 => "news"
    ];

    public static $testCentersForTask = [
        '本土日化服饰组',
        '华东食品饮料一组',
        '大客户组',
        '游戏销售组',
        '行业拓展三组',
        '西区区域及渠道组',
        '综合三组',
        'WPP集团业务群组',
    ];

    /**
     * 下载任务需要的头信息
     * @var array
     */
    public static $downloadHeaders = ["季度", "姓名", "所在组", "上级姓名", "target_id", "parent_id", "channel", "level"];

    /**
     * 获取整体范围的target id
     * @param $group
     * @return string
     */
    public static function getGroupTargetId($group)
    {
        $groupInfo = self::$groups[$group];
        return $groupInfo['group'];
    }

    public static function getGroupComment($group)
    {
        $groupInfo = self::$groups[$group];
        return $groupInfo['comment'];
    }

    /**
     * 根据用户的角色，获取任务纬度配置的key
     * @param $channel
     * @return string
     */
    public static function getTaskDimensionConfigKey($channel)
    {
        return "task_dimension_$channel";
    }

    /**
     * 获取运营管理的总监组
     * @param $groupId - direct/channel
     * @return string
     */
    public static function getOperatorTaskTargetsKey($groupId)
    {
        return "operator_director_target_{$groupId}";
    }

    public static function convertDetail($name)
    {
        switch ($name) {
            case "brand":
                return "brand.total";
            case "video":
                return "brand.video";
            case "news":
                return "brand.news";
            default:
                return $name;
        }
    }

    public static function revertDetail($name)
    {
        switch ($name) {
            case "brand.total":
                return "brand";
            case "brand.video":
                return "video";
            case "brand.news":
                return "news";
            default:
                return $name;
        }
    }

    public static function convertMoney($money, $unit = 1000)
    {
        if (!$unit) {
            $unit = 1;
        }
        return intval($money / $unit);
    }

    public static function revertMoney($money, $unit = 1000)
    {
        if (!$unit) {
            $unit = 1;
        }
        return intval($money * $unit);
    }

    public static function convertArchitectLevelToTaskLevel($level)
    {
        switch ($level) {
            case ArchitectConstant::TEAM_LEVEL_DEPT:
                return self::LEVEL_DEPT;
            case ArchitectConstant::TEAM_LEVEL_AREA:
                return self::LEVEL_AREA;
            case ArchitectConstant::TEAM_LEVEL_DIRECTOR:
                return self::LEVEL_CENTER;
            case ArchitectConstant::TEAM_LEVEL_LEADER:
                return self::LEVEL_TEAM;
        }
    }

}