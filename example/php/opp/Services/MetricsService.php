<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;

class MetricsService
{
    /**
     * WIP丢单率：商机从WIP变为失单的数量与WIP总数量占比
     *
     * @param Carbon $begin
     * @param Carbon $end
     * @return float
     */
    public static function getWipLoseRate(Carbon $begin, Carbon $end)
    {
        /** @var Connection $db */
        $db = DB::connection('crm_brand');
        $sql = "SELECT sum(CASE WHEN b.opp_id IS NOT NULL THEN 1 ELSE 0 END) AS wip_lose,
                        count(1) AS total,
                        sum(CASE WHEN b.opp_id IS NOT NULL THEN 1 ELSE 0 END) / count(1) * 100 AS wip_lost_rate
                FROM (
                    SELECT DISTINCT Fopportunity_id AS opp_id
                    FROM t_opp_process
                    WHERE Fis_del = 0 AND Fstep = 5 AND Fcreated_at >= :begin_time1 AND Fcreated_at < :end_time
                ) a LEFT JOIN (
                    SELECT DISTINCT Fopportunity_id AS opp_id
                    FROM t_opp_process
                    WHERE Fis_del = 0 AND Fstep = 7 AND Fcreated_at >= :begin_time2
                ) b ON a.opp_id = b.opp_id";
        $result = $db->select($sql, ['begin_time1' => $begin, 'end_time' => $end, 'begin_time2' => $begin]);
        if (!isset($result[0])) {
            return 0;
        }
        if (!isset($result[0]->wip_lost_rate)) {
            return 0;
        }
        return round($result[0]->wip_lost_rate, 2);
    }

    /**
     * 商机准确率：下单金额与商机预估金额差异在10%以内则视为该条准确
     *
     * @param Carbon $begin
     * @param Carbon $end
     * @return float
     */
    public static function getAccuracyRate(Carbon $begin, Carbon $end)
    {
        /** @var Connection $db */
        $db = DB::connection('crm_brand');
        $sql = "SELECT sum(correct) / count(1) * 100 AS accuracy_rate 
                FROM (SELECT Fopp_name,
                        Fforecast_money,
                        Forder_money,
                        abs(Fforecast_money - Forder_money) / Fforecast_money AS deviation,
                        CASE
                            WHEN abs(Fforecast_money - Forder_money) / Fforecast_money <= 0.1 THEN 1
                        ELSE 0
                        END AS correct
                      FROM t_opp
                      WHERE Fis_del = 0 AND Forder_money > 0 AND Fcreated_at >= :begin_time AND Fcreated_at < :end_time
                      ) tmp";
        $result = $db->select($sql, ['begin_time' => $begin, 'end_time' => $end]);
        if (!isset($result[0])) {
            return 0;
        }
        if (!isset($result[0]->accuracy_rate)) {
            return 0;
        }
        return round($result[0]->accuracy_rate, 2);
    }

    /**
     * 商机前置率：赢单前两周已经创建了商机的占总商机比例
     *
     * @param Carbon $begin
     * @param Carbon $end
     * @return float
     */
    public static function getPrepositionRate(Carbon $begin, Carbon $end)
    {
        /** @var Connection $db */
        $db = DB::connection('crm_brand');
        $sql = "SELECT SUM(CASE WHEN (UNIX_TIMESTAMP(b.created_at) - UNIX_TIMESTAMP(a.created_at)) / 86400 > 14 THEN 1 ELSE 0 END) AS preposition,
                       count(1) AS total,
                       SUM(CASE WHEN (UNIX_TIMESTAMP(b.created_at) - UNIX_TIMESTAMP(a.created_at)) / 86400 > 14 THEN 1 ELSE 0 END) / count(1) * 100 AS preposition_rate
                FROM (
                    SELECT Fopportunity_id AS opp_id, max(Fcreated_at) AS created_at
                    FROM t_opp_process
                    WHERE Fis_del = 0 AND Fstep IN (2, 5) AND Fcreated_at >= :begin_time1 AND Fcreated_at < :end_time
                    GROUP BY Fopportunity_id
                ) a JOIN (	
                    SELECT Fopportunity_id AS opp_id, max(Fcreated_at) AS created_at
                    FROM t_opp_process
                    WHERE Fis_del = 0 AND Fstep = 6 AND Fcreated_at >= :begin_time2
                    GROUP BY Fopportunity_id
                ) b ON a.opp_id = b.opp_id";
        $result = $db->select($sql, ['begin_time1' => $begin, 'end_time' => $end, 'begin_time2' => $begin]);
        if (!isset($result[0])) {
            return 0;
        }
        if (!isset($result[0]->preposition_rate)) {
            return 0;
        }
        return round($result[0]->preposition_rate, 2);
    }
}
