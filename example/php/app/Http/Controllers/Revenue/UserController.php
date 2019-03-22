<?php

namespace App\Http\Controllers\Revenue;


use App\Http\Controllers\Controller;
use App\Library\User;
use App\Services\Revenue\ArchitectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @api {get} /revenue/roles 获取用户的角色
     * @apiDescription 获取用户的角色
     * @apiGroup Revenue
     * @apiVersion 1.0.0
     * @apiParam {Integer} year
     * @apiParam {Integer} quarter
     * @apiSuccessExample {json} 正确返回值:
     * {"code":0,"msg":"OK","data":[
     * {"sale_id":"5B7F2A84-3EB7-11E7-88C5-ECF4BBC3BE1C","team_type":"1","role_type":2,"role_name":"\u603b\u76d1","team_id":"E5233EAD-8642-11E7-B486-ECF4BBC3BE1C","team_name":"KA\u516b\u7ec4","name":"\u5415\u6548\u76ca(KA\u516b\u7ec4)","channel_type":"direct"},
     * {"sale_id":"5B7F2A84-3EB7-11E7-88C5-ECF4BBC3BE1C","team_type":"1","role_type":2,"role_name":"\u603b\u76d1","team_id":"A5D59EEB-16A9-11E7-88C5-ECF4BBC3BE1C","team_name":"KA\u4e09\u7ec4","name":"\u5415\u6548\u76ca(KA\u4e09\u7ec4)","channel_type":"channel"}]}
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roles(Request $request)
    {
        /**
         * @var User $user
         * @var ArchitectService $revenueArchitectService
         */
        $user = Auth::user();
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $revenueArchitectService = app(ArchitectService::class);
        $data = $revenueArchitectService->getUserRoles($user, $year, $quarter);
        return $this->success($data);
    }
}
