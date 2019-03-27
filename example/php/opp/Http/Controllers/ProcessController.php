<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Http\Hydrators\ProcessHydrator;
use App\Http\Resources\ProcessResource;
use Illuminate\Http\Request;
use App\Repositories\ProcessDatabaseRepository;
use App\Repositories\ProcessFilter;
use App\Models\Opportunity;

class ProcessController extends Controller
{
    /**
     * @api      {POST} /opportunity-process 创建商机阶段
     * @apiGroup Process
     * @apiName  CreateProcess
     * @apiUse	CreateProcessParams
     *
     * @apiUse   ProcessItemResource
     * @apiUse   NotFound
     *
     * @throws \Throwable
     */
    public function create(Request $request)
    {
        try {
            $opportunityId = $request->input('opportunity_id');

            $opportunity = Opportunity::findByOpportunityIdOrFail($opportunityId);
            $process     = $this->hydrate(new Process(), new ProcessHydrator());
            return ProcessResource::item($process);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }

    /**
     * @api      {GET} /opportunities/{opportunity_id}/processes 获取商机进程列表
     * @apiGroup Opportunity
     * @apiName  ListProcess
     *
     * @apiParam {String} opportunity_id 商机 ID 非必填
     * @apiUse   ProcessCollectionResource
     * @apiUse   NotFound
     *
     * @param Request                   $request
     * @param                           $opportunity_id
     * @param ProcessDatabaseRepository $repository
     *
     * @return \App\Library\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, $opportunity_id, ProcessDatabaseRepository $repository)
    {
        try {
            $filter    = new ProcessFilter($request, $opportunity_id);
            $paginator = $repository->getPaginator($filter);
            return ProcessResource::collection($paginator);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }
}
