<?php

namespace App\Services\OpenAPI;

class OpenAPIService extends OpenAPIClient
{
    /**
     * 获取所有招商标签
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getAllMerchantTags($page = 1, $pageSize = 20)
    {
        $params = [
            'page' => $page,
            'page_size' => $pageSize
        ];
        return $this->sendRequest('POST', '/planning/proporsal/getbatchtags', $params);
    }

    /**
     * 通过ID获取招商标签
     * @param array $ids
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getMerchantTagsByIds(array $ids, $page = 1, $pageSize = 20)
    {
        $params = [
            'ids' => implode(',', $ids),
            'page' => $page,
            'page_size' => $pageSize
        ];
        return $this->sendRequest('POST', '/planning/proporsal/getbatchtags', $params);
    }

    /**
     * 通过关键字搜索招商标签
     * @param string $keyword
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getMerchantTagsByKeyword($keyword, $page = 1, $pageSize = 20)
    {
        $params = [
            'keyword' => $keyword,
            'page' => $page,
            'page_size' => $pageSize,
        ];
        return $this->sendRequest('POST', '/planning/proporsal/getbatchtags', $params);
    }
}
