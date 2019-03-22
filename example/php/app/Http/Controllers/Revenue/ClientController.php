<?php

namespace App\Http\Controllers\Revenue;

use App\Constant\ProjectConst;
use App\Constant\RevenueConst;
use App\Exceptions\API\ValidationFailed;
use App\Http\Controllers\Controller;
use App\Services\Client\ClientService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{

    /**
     * 下单归属
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userArchitect(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        // 业务逻辑
        $clientService = app(ClientService::class);
        $data = $clientService->userArchitect($clientId, $begin, $end);

        // 返回
        return $this->success($data);
    }

    /**
     * 下单概览
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clientOverview(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        $saleId = empty($request->input('sale_id')) ? null : $request->input('sale_id');
        $teamId = empty($request->input('team_id')) ? null : $request->input('team_id');
        // 业务逻辑
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = '您并没有该客户的存取权限';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        $data = [
            'info' => [],
            'detail' => [],
        ];
        $data['info'] = $clientService->clientOverviewInfo($clientId);
        $data['detail'] = $clientService->clientOverviewDetail($clientId, $begin, $end, $saleId, $teamId);

        // 返回
        return $this->success($data);
    }

    /**
     * 下单趋势
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clientTrend(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'time_range' => [
                'sometimes',
                'string',
                Rule::in([
                    RevenueConst::TIME_RANGE_TYPE_DAILY,
                    RevenueConst::TIME_RANGE_TYPE_WEEKLY,
                    RevenueConst::TIME_RANGE_TYPE_MONTHLY,
                    RevenueConst::TIME_RANGE_TYPE_QUARTERLY,
                ]),
            ],
            'data_type' => [
                'required',
                'string',
                Rule::in([
                    RevenueConst::TREND_DATA_TYPE_VALUE,
                    RevenueConst::TREND_DATA_TYPE_RATIO,
                ]),
            ],
            'dimension' => [
                'required',
                'string',
                Rule::in([
                    ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI,
                    ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD,
                ]),
            ],
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
            'confirm' => 'sometimes|string|max:10',
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        $timeRange = $request->input('time_range', RevenueConst::TIME_RANGE_TYPE_DAILY);
        $dataType = $request->input('data_type');
        $dimension = $request->input('dimension');
        $saleId = empty($request->input('sale_id')) ? null : $request->input('sale_id');
        $teamId = empty($request->input('team_id')) ? null : $request->input('team_id');
        $confirm = $request->input('confirm', null);
        $clientService = app(ClientService::class);
        $timeRange = $clientService->getTimeRangeByDate($begin, $end, $timeRange);
        if ($timeRange == false) {
            $message = '时间区间与粒度参数不合规定';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = '您并没有该客户的存取权限';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        $data = $clientService->clientTrend(
            $clientId,
            $begin,
            $end,
            $timeRange,
            $dataType,
            $dimension,
            $saleId,
            $teamId,
            ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
            $confirm
        );

        // 返回
        return $this->success($data);
    }

    /**
     * 下单趋势导出
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function clientTrendExportFile(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'time_range' => [
                'sometimes',
                'string',
                Rule::in([
                    RevenueConst::TIME_RANGE_TYPE_DAILY,
                    RevenueConst::TIME_RANGE_TYPE_WEEKLY,
                    RevenueConst::TIME_RANGE_TYPE_MONTHLY,
                    RevenueConst::TIME_RANGE_TYPE_QUARTERLY,
                ]),
            ],
            'data_type' => [
                'required',
                'string',
                Rule::in([
                    RevenueConst::TREND_DATA_TYPE_VALUE,
                    RevenueConst::TREND_DATA_TYPE_RATIO,
                ]),
            ],
            'dimension' => [
                'required',
                'string',
                Rule::in([
                    ProjectConst::CLIENT_ORDER_DIMENSION_PRODUCT_ARCHI,
                    ProjectConst::CLIENT_ORDER_DIMENSION_SELL_METHOD,
                ]),
            ],
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        $timeRange = $request->input('time_range', '');
        $dataType = $request->input('data_type');
        $dimension = $request->input('dimension');
        $saleId = empty($request->input('sale_id')) ? null : $request->input('sale_id');
        $teamId = empty($request->input('team_id')) ? null : $request->input('team_id');
        $clientService = app(ClientService::class);
        $timeRange = $clientService->getTimeRangeByDate($begin, $end, $timeRange);
        if ($timeRange == false) {
            $message = '时间区间与粒度参数不合规定';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = '您并没有该客户的存取权限';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        $data = $clientService->clientTrendExportFile(
            $clientId,
            $begin,
            $end,
            $timeRange,
            $dataType,
            $dimension,
            $saleId,
            $teamId
        );

        // 返回
        return response()->download($data['file'], $data['name'], $data['header']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTime(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'channel_type' => 'sometimes|string|max:36',
        ]);
        $channelType = $request->input('channel_type', ProjectConst::SALE_CHANNEL_TYPE_DIRECT);
        /**
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);
        $updateTime = $clientService->getUpdateTime($channelType);
        // 返回
        return $this->success(['update_time' => $updateTime]);
    }
}
