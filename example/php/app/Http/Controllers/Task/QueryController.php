<?php

namespace App\Http\Controllers\Task;

use App\Constant\TaskConstant;
use App\Exceptions\API\Forbidden;
use App\Http\Controllers\Controller;
use App\Library\User;
use App\MicroService\TaskClient;
use App\Services\Task\ArchitectService;
use App\Services\Task\QueryService;
use App\Services\Task\TaskService;
use App\Services\Tree\Tree;
use App\Utils\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class QueryController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class QueryController extends Controller
{
    public function query(Request $request)
    {
        $this->validate($request, [
            'period' => 'required|string',
            'group' => 'sometimes|string',
        ]);

        $period = $request->input('period');
        $group = $request->input('group');

        /**
         * @var User $user
         * @var QueryService $queryService
         */
        $user = Auth::user();
        $queryService = app()->make(QueryService::class);
        $tree = $queryService->query($user, $period, $group);
        return $this->success($tree);
    }

    public function download(Request $request)
    {
        $this->authorize(TaskConstant::POLICY_TASK_QUERY);
        $this->validate($request, [
            'period' => 'required|string',
            'group' => 'sometimes|string',
        ]);
        $period = $request->input('period');
        $group = $request->input('group');
        $periods = explode(',', $period);
        /**
         * @var User $user
         * @var QueryService $queryService
         */
        $user = Auth::user();
        $queryService = app()->make(QueryService::class);

        $treeMap = [];
        foreach ($periods as $period) {
            $tree = $queryService->query($user, $period, $group);
            $treeMap[$period] = $tree;
        }
        $data = $queryService->formatTreeForExport($treeMap);
        $downloadInfo = $queryService->generateDownloadFile($data);
        return response()->download($downloadInfo['file'], $downloadInfo['name'], $downloadInfo['header']);
    }

    public function template(Request $request)
    {
        $this->authorize(TaskConstant::POLICY_TASK_UPLOAD);
        $this->validate($request, [
            'period' => 'required|string',
            'group' => 'required|string',
        ]);
        $period = $request->input('period');
        $group = $request->input('group');
        /**
         * @var User $user
         * @var QueryService $queryService
         */
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOperator()) {
            throw new Forbidden("没有权限下载任务模板");
        }
        $queryService = app()->make(QueryService::class);
        $tree = $queryService->query($user, $period, $group);
        $treeMap[$period] = $tree;
        $data = $queryService->formatTreeForExport($treeMap);
        $downloadInfo = $queryService->generateDownloadFile($data, "销售任务模板");
        return response()->download($downloadInfo['file'], $downloadInfo['name'], $downloadInfo['header']);
    }

    public function upload(Request $request)
    {
        $this->authorize(TaskConstant::POLICY_TASK_UPLOAD);
        $this->validate($request, [
            'period' => 'required|string',
            'group' => 'required|string'
        ]);
        $period = $request->input('period');
        $group = $request->input('group');
        $file = $request->file('file');
        /**
         * @var User $user
         * @var TaskService $taskService
         * @var TaskClient $taskClient
         */
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isOperator()) {
            throw new Forbidden("没有权限下载任务模板");
        }
        $taskService = app()->make(TaskService::class);
        $dimensions = $taskService->getTaskDimension($group);
        $tasks = $taskService->readTaskFromFile($file, $dimensions);
        $taskClient = app()->make(TaskClient::class);
        $ret = $taskClient->upload(['period' => $period, 'tasks' => $tasks]);
        return $this->success($ret['data']);
    }
}
