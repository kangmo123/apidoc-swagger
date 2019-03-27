<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\API\ValidationFailed;
use App\Services\Workbench\WorkbenchService;
use App\Http\Resources\MerchantProjectResource;
use App\Repositories\MerchantProjects\MerchantProjectFilter;
use App\Repositories\MerchantProjects\MerchantProjectRepository;

class MerchantProjectController extends Controller
{
    /**
     * @api {GET} /merchant-projects 招商项目列表
     * @apiGroup MerchantProjects
     * @apiName MerchantProjectList
     *
     * @apiUse MerchantProjectFilter
     * @apiUse MerchantProjectCollectionResource
     */
    /**
     * @param Request $request
     * @param MerchantProjectRepository $repository
     * @return \App\Library\Http\Resources\Json\ResourceCollection
     */
    public function index(Request $request, MerchantProjectRepository $repository)
    {
        return MerchantProjectResource::collection($repository->getPaginator(new MerchantProjectFilter($request)));
    }

    /**
     * @api {GET} /merchant-projects/auto-complete 招商项目名称自动完成
     * @apiGroup MerchantProjects
     * @apiName MerchantProjectAutoComplete
     *
     * @apiParam {String} project_name 招商项目名称,模糊匹配
     * @apiSuccessExample 返回的招商项目名称资源
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": [
     *         {
     *             "project_code": "2018032300016",
     *             "project_name": "演员的诞生"
     *         },
     *         {
     *             "project_code": "2018042000007",
     *             "project_name": "如懿传"
     *         }
     *     ],
     * }
     */
    /**
     * @param Request $request
     * @param WorkbenchService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoComplete(Request $request, WorkbenchService $service)
    {
        $projectName = $request->get('project_name');
        if (empty($projectName)) {
            throw new ValidationFailed(0, '请传递招商项目名称');
        }
        return $this->success($service->completeProjectByName($projectName));
    }
}
