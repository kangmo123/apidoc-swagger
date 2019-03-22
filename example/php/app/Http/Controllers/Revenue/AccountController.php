<?php

namespace App\Http\Controllers\Revenue;

use App\Constant\ProjectConst;
use App\Http\Controllers\Controller;
use App\Services\Account\AccountService;
use App\Services\Client\ClientService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\API\ValidationFailed;

class AccountController extends Controller
{
    
    /**
     * 效果消耗数据
     *
     * @param Request $request
     *
     * @return Response
     */
    public function show(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id'  => 'required|string|max:36',
            'begin'      => 'required|date_format:Y-m-d',
            'end'        => 'required|date_format:Y-m-d',
            'sale_id'    => 'sometimes|string|max:36',
            'team_id'    => 'sometimes|string|max:36',
            'account_id' => "sometimes|string|max:50",
            'short_id'   => "sometimes|string|max:36",
            'page'       => 'sometimes|integer|min:0|max:500',
            'per_page'   => 'sometimes|integer|min:0|max:100',
            'confirm'    => 'sometimes|string|max:10',
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        $saleId = empty($request->input('sale_id')) ? null : $request->input('sale_id');
        $teamId = empty($request->input('team_id')) ? null : $request->input('team_id');
        $accountId = empty($request->input('account_id')) ? null : $request->input('account_id');
        $shortId = empty($request->input('short_id')) ? null : $request->input('short_id');
        $page = $request->input('page', ProjectConst::DEFAULT_PAGE);
        $perPage = $request->input('per_page', ProjectConst::DEFAULT_PAGE_SIZE);
        $confirm = $request->input('confirm', null);
        // 业务逻辑
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = '您并没有该客户的存取权限';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        $accountService = app(AccountService::class);
        $pageInfo = [];
        $data = $accountService->getRevenueEffect(
            $clientId,
            $begin,
            $end,
            $saleId,
            $teamId,
            ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
            $accountId,
            $shortId,
            $page,
            $perPage,
            $confirm,
            $pageInfo
        );
        
        // 返回
        return new Response([
            "code"      => 0,
            "msg"       => "OK",
            "data"      => $data,
            "page_info" => $pageInfo
        ]);
    }
    
    /**
     * 效果消耗数据导出
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        // 收参数验证
        $this->validate($request, [
            'client_id'  => 'required|string|max:36',
            'begin'      => 'required|date_format:Y-m-d',
            'end'        => 'required|date_format:Y-m-d',
            'sale_id'    => 'sometimes|string|max:36',
            'team_id'    => 'sometimes|string|max:36',
            'account_id' => "sometimes|string|max:50",
            'short_id'   => "sometimes|string|max:36",
        ]);
        $clientId = $request->input('client_id');
        $begin = $request->input('begin');
        $end = $request->input('end');
        $saleId = empty($request->input('sale_id')) ? null : $request->input('sale_id');
        $teamId = empty($request->input('team_id')) ? null : $request->input('team_id');
        $accountId = empty($request->input('account_id')) ? null : $request->input('account_id');
        $shortId = empty($request->input('short_id')) ? null : $request->input('short_id');
        // 业务逻辑
        $clientService = app(ClientService::class);
        if (!$clientService->checkSaleClientPrivilege($clientId, $begin, $end)) {
            $message = '您并没有该客户的存取权限';
            throw new ValidationFailed($message, 0, ['error' => $message]);
        }
        $accountService = app(AccountService::class);
        $data = $accountService->getRevenueEffectExportFile(
            $clientId,
            $begin,
            $end,
            $saleId,
            $teamId,
            ProjectConst::SALE_CHANNEL_TYPE_DIRECT,
            $accountId,
            $shortId
        );
        
        // 返回
        return response()->download($data['file'], $data['name'], $data['header']);
    }
}
