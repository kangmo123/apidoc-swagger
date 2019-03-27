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
 * @apiDefine QuarterFilter
 *
 * @apiParam {Number} quarter 季度
 * @apiParam {Number} year 年份
 *
 **/
class QuarterFilter
{
    use SelfName;

    protected $year;

    protected $quarter;

    public function __construct(Request $request)
    {
        $this->year = $request->get('year');
        $this->quarter = $request->get('quarter');
    }

    /**
     * @return mixed
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @return mixed
     */
    public function getQuarter()
    {
        return $this->quarter;
    }
}
