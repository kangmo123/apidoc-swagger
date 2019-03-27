<?php

namespace App\Imports;

use Maatwebsite\Excel\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\MerchantTags\PolicyGrade;
use App\Models\MerchantTags\MerchantTag;
use App\Services\OpenAPI\OpenAPIService;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class PolicyGradeImport implements ToModel, WithMultipleSheets, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use Importable;

    /**
     * @var int
     */
    const INSERT_BATCH_SIZE = 1000;

    /**
     * @var int
     */
    const READ_CHUCK_SIZE = 1000;

    /**
     * @var string
     */
    protected $readerType = Excel::XLSX;

    /**
     * @var int
     */
    protected $insertCount = 0;

    /**
     * @var int
     */
    protected $updateCount = 0;

    /**
     * @var ImportError
     */
    protected $importError;

    /**
     * @var OpenAPIService
     */
    protected $openApiService;

    /**
     * @var array
     */
    const HEADERS = [
        '招商标签',
        '政策标签',
        '政策标签开始时间',
        '政策标签结束时间',
    ];

    /**
     * PolicyGradeImport constructor.
     */
    public function __construct()
    {
        HeadingRowFormatter::default('none');
        $this->importError = new ImportError();
        $this->openApiService = new OpenAPIService();
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $row = $this->trimRow($row);
        $merchantTag = $row['招商标签'];
        $policyGrade = $row['政策标签'];
        //进行数据格式校验
        if (!in_array($policyGrade, PolicyGrade::GRADE_MAPS)) {
            $this->importError->appendError($row, '政策标签不正确，只能是' . implode(', ', PolicyGrade::GRADE_MAPS));
            return null;
        }
        try {
            $beginDate = Carbon::instance(Date::excelToDateTimeObject($row['政策标签开始时间']));
            $row['政策标签开始时间'] = $beginDate;
        } catch (\Exception $e) {
            $this->importError->appendError($row, '政策标签开始时间不是正确时间格式');
            return null;
        }
        try {
            $endDate = Carbon::instance(Date::excelToDateTimeObject($row['政策标签结束时间']));
            $row['政策标签结束时间'] = $endDate;
        } catch (\Exception $e) {
            $this->importError->appendError($row, '政策标签结束时间不是正确时间格式');
            return null;
        }
        if ($endDate->lessThanOrEqualTo($beginDate)) {
            $this->importError->appendError($row, '政策标签结束时间小于开始时间');
            return null;
        }
        //进行数据库和API校验
        $merchantTagModel = MerchantTag::findByTag($merchantTag);
        if (!$merchantTagModel) {
            $merchantTagFromAPI = $this->openApiService->getMerchantTagsByKeyword($merchantTag)['records'];
            if (count($merchantTagFromAPI) === 0) {
                $this->importError->appendError($row, '通过招商标签名称没有搜索到招商标签，请确认标签名称');
                return null;
            }
            if (count($merchantTagFromAPI) !== 1) {
                $matchTag = [];
                foreach ($merchantTagFromAPI as $index => $apiData) {
                    if ($apiData['Ftag'] === $merchantTag) {
                        $matchTag = $merchantTagFromAPI[$index];
                        break;
                    }
                }
                if (empty($matchTag)) {
                    $this->importError->appendError($row, '通过招商标签名称搜索到多个招商标签，无法唯一绑定');
                    return null;
                }
                $merchantTagFromAPI = [$matchTag];
            }
            $merchantTagFromAPI = array_pop($merchantTagFromAPI);
            $merchantTagModel = MerchantTag::buildFromAPIData($merchantTagFromAPI);
            $merchantTagModel->save();
        }
        if (count($merchantTagModel->policyGrades)) {
            $this->importError->appendError($row, '招商标签已绑定过政策标签, 请使用修改功能');
            return null;
        }
        //构建政策标签对象
        $policyGradeModel = new PolicyGrade();
        $policyGradeModel->tag_id = $merchantTagModel->id;
        $policyGradeModel->policy_grade = array_search($policyGrade, PolicyGrade::GRADE_MAPS);
        $policyGradeModel->begin_date = $beginDate;
        $policyGradeModel->end_date = $endDate;
        $policyGradeModel->created_by = Auth::user()->getAuthIdentifier();
        $policyGradeModel->updated_by = Auth::user()->getAuthIdentifier();
        $this->insertCount++;
        return $policyGradeModel;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function trimRow(array $row)
    {
        $result = [];
        foreach (self::HEADERS as $key) {
            $result[$key] = $row[$key] ?? '';
        }
        return $result;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            $this,
        ];
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return self::INSERT_BATCH_SIZE;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return self::READ_CHUCK_SIZE;
    }

    /**
     * @return int
     */
    public function getInsertCount()
    {
        return $this->insertCount;
    }

    /**
     * @return int
     */
    public function getUpdateCount()
    {
        return $this->updateCount;
    }

    /**
     * @return ImportError
     */
    public function getImportError()
    {
        return $this->importError;
    }
}
