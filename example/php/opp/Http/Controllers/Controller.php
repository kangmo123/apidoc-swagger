<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Model;
use App\Http\Hydrators\Hydrator;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Exceptions\Business\ParamException;
use App\Exceptions\Business\BusinessException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

abstract class Controller extends BaseController
{
    /**
     * @var array 所有需要跟事务一起提交或者回滚的DB连接
     */
    protected $connectionsToTransact = [null];

    /**
     * @apiDefine         ReturnFail
     * @apiSuccessExample 返回错误示例
     * {
     *  "code": 2000,
     *  "msg": "错误原因",
     * }
     */

    /**
     * 返回错误.
     *
     * @param $code
     * @param $msg
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function fail($code, $msg)
    {
        $ret = [
            'code' => $code,
            'msg'  => $msg,
        ];
        return response()->json($ret);
    }

    /**
     * @apiDefine         ReturnSuccess
     * @apiSuccessExample 返回正确示例
     * {
     *  "code": 0,
     *  "msg": "OK",
     *  "data": {}
     */

    /**
     * 返回正确.
     *
     * @param array|null $data
     * @param string     $msg
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(array $data = [], $msg = "OK")
    {
        $result = [
            "code" => 0,
            "msg" => $msg,
        ];
        if (!empty($data)) {
            $result['data'] = $data;
        } else {
            $result['data'] = [];
        }
        return response()->json($result);
    }

    /**
     * 使用Request数据来填充数据模型.
     *
     * @param Model    $model
     * @param Hydrator $hydrator
     *
     * @return Model
     *
     * @throws Throwable
     */
    public function hydrate(Model $model, Hydrator $hydrator)
    {
        try {
            $this->beginTransaction();
            $result = $hydrator->hydrate(app('request')->all(), $model);
            $this->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * 开启所有DB连接的事务
     */
    protected function beginTransaction()
    {
        $database = app('db');
        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->beginTransaction();
        }
    }

    /**
     * 提交所有DB连接的事务
     */
    protected function commit()
    {
        $database = app('db');
        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->commit();
        }
    }

    /**
     * 回滚所有DB连接的事务
     */
    protected function rollBack()
    {
        $database = app('db');
        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->rollBack();
        }
    }

    /**
     * 获取所有有事务的数据库连接.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
            ? $this->connectionsToTransact : [null];
    }

    /**
     * 业务异常处理.
     *
     * @param       $e         \Exception
     * @param array $reportKey 选填，进行上报的custom_key
     */
    public function dealException($e, $reportKey = null)
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if (empty($reportKey)) {
            $reportKey = app('request')->getPathInfo();
        }
        if (!($e instanceof ParamException) && !($e instanceof BusinessException)) {
            Log::error('error: >>> errorCode:' . $code . ';stack trace:' . $e->getTraceAsString());
        }
        Log::warning('warnning: >>> errorCode:' . $code . ';stack trace:' . $e->getTraceAsString());
        return $this->fail($code, $message);
    }
}
