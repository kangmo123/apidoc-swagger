<?php

namespace App\Jobs;

use App\Exports\ExportError;
use App\Imports\PolicyGradeImport;
use App\Library\Auth\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ImportStatusService;
use Illuminate\Filesystem\FilesystemManager;

class PolicyGradeImportJob extends Job
{
    /**
     * @var string
     */
    public $connection = 'redis';

    /**
     * @var string
     */
    public $queue = 'import';

    /**
     * @var int
     */
    public $timeout = 360;

    /**
     * @var string
     */
    protected $taskId;

    /**
     * @var string
     */
    protected $userName;

    /**
     * @var string
     */
    protected $fileContent;

    /**
     * @var string
     */
    const STORAGE_DISK = 'log';

    /**
     * @var string
     */
    const TMP_FILE_PREFIX = 'import/import-';

    /**
     * @var string
     */
    const TMP_FILE_SUFFIX = '.xlsx';

    /**
     * PolicyGradeImportJob constructor.
     * @param $taskId
     * @param $userName
     * @param $fileContent
     */
    public function __construct($taskId, $userName, $fileContent)
    {
        $this->taskId = $taskId;
        $this->userName = $userName;
        $this->fileContent = $fileContent;
    }

    public function handle()
    {
        //准备工作
        Auth::setUser(new User($this->userName));
        /** @var FilesystemManager $storage */
        $storage = app('filesystem');
        $disk = $storage->disk(self::STORAGE_DISK);
        $fileName = self::TMP_FILE_PREFIX . $this->taskId . self::TMP_FILE_SUFFIX;
        $success = $disk->put($fileName, base64_decode($this->fileContent));
        if (!$success) {
            throw new \RuntimeException("保存临时文件失败");
        }
        //开始导入
        Log::info("Import Job: ". $this->taskId);
        Log::info("UserName: ". Auth::user()->getAuthIdentifier());
        $importer = new PolicyGradeImport();
        $importer->import($fileName, self::STORAGE_DISK);
        Log::info("Totally Inserted: ". $importer->getInsertCount());
        Log::info("Totally Updated: ". $importer->getUpdateCount());
        //标记状态
        $statusService = new ImportStatusService($this->taskId);
        if ($importer->getImportError()->hasErrors()) {
            $errors = $importer->getImportError()->getErrors();
            Log::warning("Import Errors: ". json_encode($errors));
            $exportFileName = self::TMP_FILE_PREFIX . 'error-'. $this->taskId . self::TMP_FILE_SUFFIX;
            $exporter = new ExportError($importer->getImportError(), $importer::HEADERS);
            $exporter->store($exportFileName, self::STORAGE_DISK);
            $statusService->fail(count($errors), $exportFileName);
        } else {
            $statusService->succeed($importer->getInsertCount() + $importer->getUpdateCount());
        }
        Log::info("Import Finished");
    }
}
