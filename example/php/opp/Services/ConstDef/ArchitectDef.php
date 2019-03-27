<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/6/29
 * Time: 7:03 PM
 */

namespace App\Services\ConstDef;

class ArchitectDef
{
    public const TYPE_CHANNEL = 0;
    public const TYPE_DEPARTMENT = 1;
    public const TYPE_AREA = 10;
    public const TYPE_CENTRE = 20;
    public const TYPE_TEAM= 30;

    public static $parentLevel =
        [
            self::TYPE_TEAM => self::TYPE_CENTRE,
            self::TYPE_CENTRE => self::TYPE_AREA,
            self::TYPE_AREA => self::TYPE_DEPARTMENT,
            self::TYPE_DEPARTMENT => self::TYPE_CHANNEL,
            self::TYPE_CHANNEL => null
        ];

    public const UNKNOWN_SALE_ID = 'C3628528-2CB6-DD11-817D-001D096CF989';
    public const UNKNOWN_TEAM_ID = 'DDFE2B3C-1EB9-87FF-BF2E-A16A64CE3DD3';
    public const UNKNOWN_CENTRE_ID = '7F12D4F2-0693-4451-844A-2BE05097CC81';
    public const UNKNOWN_AREA_ID = '61C38E90-1CE8-950B-4018-2D0541B18197';
    public const UNKNOWN_DEPARTMENT_ID = 'E3259CC6-77E6-4D26-252E-B6E1AE08D886';
    public const UNKNOWN_CHANNEL_ID = '887303D1-16A8-11E7-88C5-ECF4BBC3BE2D';

}
