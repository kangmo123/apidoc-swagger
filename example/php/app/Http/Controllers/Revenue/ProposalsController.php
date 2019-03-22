<?php

namespace App\Http\Controllers\Revenue;

use App\Constant\ProjectConst;
use App\Http\Controllers\Controller;
use App\Services\Client\ClientService;
use App\Services\Client\ProposalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exceptions\API\ValidationFailed;

class ProposalsController extends Controller
{
    /**
     * 品牌排期列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function show(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
            'page' => 'sometimes|integer|min:0|max:500',
            'per_page' => 'sometimes|integer|min:0|max:100',
            'schedule_code' => 'sometimes|string|max:100',
            'channel_type' => [
                'sometimes',
                'string',
                Rule::in([
                    ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
                    ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                ]),
            ],
        ]);
        $clientId = $request->input('client_id');
        $begin = Carbon::make($request->input('begin'))->format('Ymd');
        $end = Carbon::make($request->input('end'))->format('Ymd');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $proposalCode = $request->input('schedule_code');
        $saleId = empty($saleId) ? null : $saleId;
        $teamId = empty($teamId) ? null : $teamId;
        $proposalCode = empty($proposalCode) ? null : $proposalCode;
        $page = $request->input('page');
        $perPage = $request->input('per_page');
        $channelType = $request->input('channel_type', ProjectConst::SALE_CHANNEL_TYPE_DIRECT);
        /**
         * 业务逻辑
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = "您并没有该客户的存取权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        /**
         * @var ProposalService $proposalService
         */
        $proposalService = app(ProposalService::class);
        $data = $proposalService->getProposalSearchList($clientId, $begin, $end, $saleId, $teamId, $proposalCode, $page,
            $perPage,
            $channelType);

        list($list, $pageInfo) = $data;
        // 返回
        $ret = [
            "code" => 0,
            "msg" => "OK",
            "data" => [
                "list" => $list,
            ],
            "page_info" => $pageInfo
        ];
        return response()->json($ret);
    }

    /**
     * 品牌排期导出
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
            'schedule_code' => 'sometimes|string|max:100',
            'page' => 'sometimes|integer|min:0|max:500',
            'per_page' => 'sometimes|integer|min:0|max:100',
            'channel_type' => [
                'sometimes',
                'string',
                Rule::in([
                    ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
                    ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                ]),
            ],
        ]);
        $clientId = $request->input('client_id');
        $begin = Carbon::make($request->input('begin'))->format('Ymd');
        $end = Carbon::make($request->input('end'))->format('Ymd');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $proposalCode = $request->input('schedule_code');
        $saleId = empty($saleId) ? null : $saleId;
        $teamId = empty($teamId) ? null : $teamId;
        $proposalCode = empty($proposalCode) ? null : $proposalCode;
        $channelType = $request->input('channel_type', ProjectConst::SALE_CHANNEL_TYPE_DIRECT);
        /**
         * 业务逻辑
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = "您并没有该客户的存取权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        /**
         * @var ProposalService $proposalService
         */
        $proposalService = app(ProposalService::class);
        $data = $proposalService->getProposalSearchList($clientId, $begin, $end, $saleId, $teamId, $proposalCode, null,
            null,
            $channelType, true);
        $data = $proposalService->exportSearchData($data);
        return response()->download($data['file'], $data['name'], $data['header']);
    }

    /**
     * 已下单未计入收入排期列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function remain(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
            'page' => 'sometimes|integer|min:0|max:500',
            'per_page' => 'sometimes|integer|min:0|max:100',
            'channel_type' => [
                'sometimes',
                'string',
                Rule::in([
                    ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
                    ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                ]),
            ],
        ]);
        $clientId = $request->input('client_id');
        $begin = Carbon::make($request->input('begin'))->format('Ymd');
        $end = Carbon::make($request->input('end'))->format('Ymd');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $proposalCode = $request->input('schedule_code');
        $saleId = empty($saleId) ? null : $saleId;
        $teamId = empty($teamId) ? null : $teamId;
        $proposalCode = empty($proposalCode) ? null : $proposalCode;
        $page = $request->input('page');
        $perPage = $request->input('per_page');
        $channelType = $request->input('channel_type', ProjectConst::SALE_CHANNEL_TYPE_DIRECT);
        /**
         * 业务逻辑
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);

        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = "您并没有该客户的存取权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }

        /**
         * @var ProposalService $proposalService
         */
        $proposalService = app(ProposalService::class);
        $data = $proposalService->getRemainProposalSearchList($clientId, $begin, $end, $saleId, $teamId, $proposalCode,
            $page,
            $perPage,
            $channelType);
        list($summary, $list, $pageInfo) = $data;
        // 返回
        $ret = [
            "code" => 0,
            "msg" => "OK",
            "data" => [
                "total_remain" => $summary,
                "list" => $list,
            ],
            "page_info" => $pageInfo
        ];
        return response()->json($ret);
    }

    /**
     * 已下单未计入收入排期导出
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function remainExport(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id' => 'required|string|max:36',
            'begin' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d',
            'sale_id' => 'sometimes|string|max:36',
            'team_id' => 'sometimes|string|max:36',
            'page' => 'sometimes|integer|min:0|max:500',
            'per_page' => 'sometimes|integer|min:0|max:100',
            'channel_type' => [
                'sometimes',
                'string',
                Rule::in([
                    ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
                    ProjectConst::SALE_CHANNEL_TYPE_CHANNEL,
                ]),
            ],
        ]);
        $clientId = $request->input('client_id');
        $begin = Carbon::make($request->input('begin'))->format('Ymd');
        $end = Carbon::make($request->input('end'))->format('Ymd');
        $saleId = $request->input('sale_id');
        $teamId = $request->input('team_id');
        $proposalCode = $request->input('schedule_code');
        $saleId = empty($saleId) ? null : $saleId;
        $teamId = empty($teamId) ? null : $teamId;
        $proposalCode = empty($proposalCode) ? null : $proposalCode;
        $channelType = $request->input('channel_type', ProjectConst::SALE_CHANNEL_TYPE_DIRECT);
        /**
         * 业务逻辑
         * @var $clientService ClientService
         */
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = "您并没有该客户的存取权限";
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        /**
         * @var ProposalService $proposalService
         */
        $proposalService = app(ProposalService::class);
        $data = $proposalService->getRemainProposalSearchList($clientId, $begin, $end, $saleId, $teamId, $proposalCode,
            null, null,
            $channelType, true);
        $data = $proposalService->exportRemainSearchData($data);
        return response()->download($data['file'], $data['name'], $data['header']);
    }

}
