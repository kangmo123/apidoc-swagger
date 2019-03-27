<?php

namespace App\Models\MerchantTags;

use Carbon\Carbon;
use App\Models\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PolicyGrade
 * @package App\Models\MerchantTags
 *
 * @property integer id
 * @property integer tag_id
 * @property string policy_grade
 * @property Carbon begin_date
 * @property Carbon end_date
 * @property Carbon created_at
 * @property string created_by
 * @property Carbon updated_at
 * @property string updated_by
 * @property Carbon deleted_at
 * @property string deleted_by
 */
class PolicyGrade extends Model
{
    use SoftDeletes;

    const GRADE_S = 'S';
    const GRADE_A = 'A';
    const GRADE_B = 'B';
    const GRADE_C = 'C';

    const GRADE_MAPS = [
        self::GRADE_S => 'S级',
        self::GRADE_A => 'A级',
        self::GRADE_B => 'B级',
        self::GRADE_C => 'C级',
    ];

    protected $connection = 'crm_sales_navi';

    protected $table = 't_merchant_policy_grade';

    protected $dates = ['deleted_at', 'begin_date', 'end_date'];

    protected static function boot()
    {
        parent::boot();
        self::creating(function (self $model) {
            $model->created_by = Auth::user()->getAuthIdentifier();
            $model->updated_by = Auth::user()->getAuthIdentifier();
        });
        self::updating(function (self $model) {
            $model->updated_by = Auth::user()->getAuthIdentifier();
        });
        self::deleted(function (self $model) {
            $model->deleted_by = Auth::user()->getAuthIdentifier();
            $model->save();
        });
    }
}
