<?php

namespace App\Exports;

use App\Imports\ImportError;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;

class ExportError implements FromArray, WithHeadings, WithMapping, ShouldAutoSize, WithCustomQuerySize, WithCustomChunkSize
{
    use Exportable;

    /**
     * @var int
     */
    const QUERY_BATCH_SIZE = 1000;

    /**
     * @var int
     */
    const WRITE_CHUCK_SIZE = 1000;

    /**
     * @var string
     */
    protected $writerType = Excel::XLSX;

    /**
     * @var ImportError
     */
    protected $importError;

    /**
     * @var array
     */
    protected $headers;

    /**
     * ExportError constructor.
     * @param ImportError $importError
     * @param array $headers
     */
    public function __construct(ImportError $importError, array $headers)
    {
        $this->importError = $importError;
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->importError->getErrors();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = $this->headers;
        $headings[] = '出错原因';
        return $headings;
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $result = $row['data'];
        $result[] = implode(', ', $row['errors']);
        return $result;
    }

    /**
     * @return int
     */
    public function querySize(): int
    {
        return self::QUERY_BATCH_SIZE;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return self::WRITE_CHUCK_SIZE;
    }
}
