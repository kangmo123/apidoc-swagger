<?php
/**
 * Created by PhpStorm.
 * User: guangyang
 * Date: 2018/7/15
 * Time: 2:16 PM
 */

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @apiDefine DateFilter
 *
 * @apiParam {Number} date 日期 , 20180101~20180401(表示20180101到20180401,不包含20180401)
 *
 */
class DateFilter
{
    use SelfName;

    protected $startTime;

    protected $endTime;

    public function __construct(Request $request)
    {
        $date = $request->get('date');
        $dateArr = explode('~', $date);
        $this->startTime = !empty($dateArr[0]) ? new Carbon($dateArr[0]) : null;
        $this->endTime = !empty($dateArr[1]) ? (new Carbon($dateArr[1]))->subDay() : null;
    }

    /**
     * @return Carbon|null
     */
    public function getStartTime(): ?Carbon
    {
        return $this->startTime;
    }

    /**
     * @return Carbon|null
     */
    public function getEndTime(): ?Carbon
    {
        return $this->endTime;
    }
}
