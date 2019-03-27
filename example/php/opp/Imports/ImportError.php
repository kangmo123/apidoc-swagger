<?php

namespace App\Imports;

class ImportError
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param array $row
     * @param $error
     */
    public function appendError(array $row, $error)
    {
        $rowIndex = $this->getRowIndex($row);
        if (!isset($this->errors[$rowIndex])) {
            $this->errors[$rowIndex] = [
                'data' => $row,
                'errors' => [],
            ];
        }
        if (is_array($error)) {
            $this->errors[$rowIndex]['errors'] = array_values(array_unique(array_merge($this->errors[$rowIndex]['errors'], $error)));
        } else {
            $this->errors[$rowIndex]['errors'][] = $error;
        }
    }

    /**
     * @param array $row
     * @return string
     */
    protected function getRowIndex(array $row)
    {
        sort($row);
        return md5(json_encode($row));
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return array_values($this->errors);
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
