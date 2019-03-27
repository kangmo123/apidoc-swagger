<?php

namespace App\Models\MerchantTags;

use Carbon\Carbon;
use App\Models\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MerchantTag
 * @package App\Models\MerchantTags
 *
 * @property integer id
 * @property Carbon begin_date
 * @property Carbon end_date
 * @property string tag
 * @property string merchant_code
 * @property Carbon created_at
 * @property string created_by
 * @property Carbon updated_at
 * @property string updated_by
 * @property Carbon deleted_at
 * @property string deleted_by
 */
class MerchantTag extends Model
{
    use SoftDeletes;

    const MERCHANT_CODE_SEPARATOR = ',';

    protected $connection = 'crm_sales_navi';

    protected $table = 't_merchant_tags';

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

    /**
     * @return array
     */
    public function getMerchantCodes()
    {
        return parseCommaString($this->merchant_code, self::MERCHANT_CODE_SEPARATOR);
    }

    /**
     * 通过Tag名字来查找
     * @param $tagName
     * @return MerchantTag|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function findByTag($tagName)
    {
        return self::query()->where('tag', '=', $tagName)->first();
    }

    /**
     * 通过API返回数据创建Model
     * @param array $params
     * @return MerchantTag
     */
    public static function buildFromAPIData(array $params)
    {
        $model = new self();
        $model->id = $params['Fid'];
        $model->begin_date = $params['Fbegin_date'];
        $model->end_date = $params['Fend_date'];
        $model->tag = $params['Ftag'];
        $model->merchant_code = implode(self::MERCHANT_CODE_SEPARATOR, parseCommaString($params['Fmerchant_code'], ';'));
        return $model;
    }

    /**
     * 招商标签关联的政策标签
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|self[]
     */
    public function policyGrades()
    {
        return $this->hasMany(PolicyGrade::class, 'tag_id', 'id');
    }

    /**
     * 招商标签当前生效的政策标签
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|self
     */
    public function policyGrade()
    {
        return $this->policyGrades()
                    ->where('begin_date', '<=', Carbon::now())
                    ->where('end_date', '>', Carbon::now());
    }
}
