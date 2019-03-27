<?php

namespace App\Http\Request;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Urameshibr\Requests\FormRequest;

/**
 * Class BaseRequest
 * @package App\Http\Request
 */
//class BaseRequest extends Request
class BaseRequest extends FormRequest
{
    /**
     * 权限控制，子类覆盖实现自己的校验逻辑
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 参数规则校验，子类覆盖实现自己的校验逻辑
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * 初始化函数，具体业务请求子类可以重写自己的初始化函数
     */
    public function init()
    {
        Log::info($this->getPathInfo() . ' params:' . json_encode($this->all()));
    }

    /**
     * 验证表单数据格式
     */
    public function validate()
    {
        $this->init();
        parent::validate();
    }
}
