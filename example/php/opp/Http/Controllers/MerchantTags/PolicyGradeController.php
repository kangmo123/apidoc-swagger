<?php

namespace App\Http\Controllers\MerchantTags;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Imports\PolicyGradeImport;
use App\Jobs\PolicyGradeImportJob;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\ImportStatusService;
use Maatwebsite\Excel\HeadingRowImport;
use App\Models\MerchantTags\MerchantTag;
use App\Exceptions\API\ValidationFailed;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use App\Http\Resources\MerchantTags\PolicyGradeResource;

class PolicyGradeController extends Controller
{
    const UPLOAD_FILE_KEY = 'file';

    const ALLOW_UPLOAD_FILE_EXT = [
        'xlsx',
    ];

    /**
     * @api {GET} /merchant-tags/{id}/policy-grades 招商标签的政策等级列表
     * @apiGroup MerchantTags
     * @apiName GetPolicyGrades
     *
     * @apiParam {Number} id 招商标签ID
     * @apiParam {String} show=current 筛选政策标签, current(当前有效政策等级), all(全部政策等级)
     * @apiUse PolicyGradeCollectionResource
     */
    /**
     * @param Request $request
     * @param $id
     * @return \App\Library\Http\Resources\Json\ResourceCollection
     */
    public function show(Request $request, $id)
    {
        $showType = $request->get('show', 'current');
        /** @var MerchantTag $merchantTag */
        $merchantTag = MerchantTag::findOrFail($id);
        $policyGrades = ($showType == 'current') ? $merchantTag->policyGrade()->get() : $merchantTag->policyGrades()->get();
        return PolicyGradeResource::collection($policyGrades);
    }

    /**
     * 创建招商标签的政策等级
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function create($id)
    {
        //TODO 这个本期不实现
        return $this->success(["创建招商标签{$id}的政策等级"]);
    }

    /**
     * 修改招商标签的政策等级
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id)
    {
        //TODO 这个本期不实现
        return $this->success(["修改招商标签{$id}的政策等级"]);
    }

    /**
     * @api {POST} /merchant-tags/policy-grades/import 导入政策等级
     * @apiGroup MerchantTags
     * @apiName ImportPolicyGrade
     * @apiSuccessExample 导入返回值
     * HTTP/1.1 202 Accepted
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "task_id": "LlXpWDMrFGJlnaimf0MVc7b93Hawn9c9S4PQoLHy"
     *     }
     * }
     */
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $taskId = str_random(32);
        $userName = Auth::user()->getAuthIdentifier();
        if (!$request->hasFile(self::UPLOAD_FILE_KEY)) {
            throw new ValidationFailed(0, '未收到上传文件');
        }
        $file = $request->file(self::UPLOAD_FILE_KEY);
        if (!$file->isValid()) {
            throw new ValidationFailed(0, '文件上传失败');
        }
        if (!in_array($file->extension(), self::ALLOW_UPLOAD_FILE_EXT)) {
            throw new ValidationFailed(0, '文件格式不支持: '. $file->extension());
        }
        $fileContent = file_get_contents($file->path());
        if (!$this->checkImportHeader($file)) {
            throw new ValidationFailed(0, '文件内容不符合模版格式');
        }
        dispatch(new PolicyGradeImportJob($taskId, $userName, base64_encode($fileContent)));
        return $this->success([
            'task_id' => $taskId,
        ])->setStatusCode(202);
    }

    /**
     * 检查Excel格式是否符合模版
     * @param UploadedFile $file
     * @return bool
     */
    protected function checkImportHeader(UploadedFile $file)
    {
        HeadingRowFormatter::default('none');
        $headers = (new HeadingRowImport())->toArray($file);
        //检查是否多个sheet
        if (count($headers) != 1) {
            return false;
        }
        $headers = array_pop($headers);
        //检查是否只有一层
        if (count($headers) != 1) {
            return false;
        }
        //检查列的名字是否一致
        $headers = array_pop($headers);
        $match = true;
        foreach (PolicyGradeImport::HEADERS as $index => $value) {
            if ($headers[$index] !== $value) {
                $match = false;
            }
        }
        return $match;
    }

    /**
     * @api {GET} /merchant-tags/policy-grades/import-status 导入政策等级状态查询
     * @apiGroup MerchantTags
     * @apiName ImportPolicyGradeStatus
     * @apiParam {String} task_id 任务ID
     * @apiSuccessExample 导入成功返回值
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "status": "success",
     *         "count": 1000
     *     }
     * }
     * @apiSuccessExample 导入失败返回值
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "status": "failure",
     *         "count": 17,
     *         "url": "http://speedtest.tokyo2.linode.com/100MB-tokyo2.bin"
     *     }
     * }
     * @apiSuccessExample 导入处理中返回值
     * HTTP/1.1 200 OK
     * {
     *     "code": 0,
     *     "msg": "OK",
     *     "data": {
     *         "status": "processing"
     *     }
     * }
     */
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importStatus(Request $request)
    {
        $taskId = $request->get('task_id');
        $statusService = new ImportStatusService($taskId);
        $status = $statusService->getStatus();
        $data = [
            'status' => $status,
        ];
        if ($status == ImportStatusService::STATUS_SUCCESS) {
            $data['count'] = $statusService->getCount();
        }
        if ($status == ImportStatusService::STATUS_FAILURE) {
            $data['count'] = $statusService->getCount();
            $data['url'] = $this->buildUrl($taskId);
        }
        return $this->success($data);
    }

    /**
     * 导入招商标签的政策等级错误下载
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function importDownload(Request $request)
    {
        $taskId = $request->get('task_id');
        $statusService = new ImportStatusService($taskId);
        $filePath = $statusService->getFile();
        if (!$filePath) {
            return abort(404);
        }
        /** @var FilesystemManager $storage */
        $storage = app('filesystem');
        /** @var FilesystemAdapter $disk */
        $disk = $storage->disk(PolicyGradeImportJob::STORAGE_DISK);
        if (!$disk->exists($filePath)) {
            return redirect()->to($this->buildUrl($taskId));
        }
        return $disk->download($filePath);
    }

    /**
     * @param $taskId
     * @return string
     */
    protected function buildUrl($taskId)
    {
        $serviceName = config('app.name');
        $params = [
            'task_id' => $taskId,
            'api_key' => config('services.gateway.api_key')
        ];
        $url = route('policy-grade-import-download', $params);
        $urlInfo = parse_url($url);
        $urlInfo['host'] = config('services.gateway.domain');
        $urlInfo['path'] = '/' . $serviceName . $urlInfo['path'];
        return build_url($urlInfo);
    }
}
