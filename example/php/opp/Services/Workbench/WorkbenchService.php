<?php

namespace App\Services\Workbench;

use App\Repositories\MerchantProjects\MerchantProjectFilter;

class WorkbenchService extends WorkbenchClient
{
    const PROJECT_STATUS_ONGOING = 2;
    const PROJECT_STATUS_OFFLINE = 3;

    const PROJECT_STATUS_MAPS = [
        self::PROJECT_STATUS_ONGOING => '招商中',
        self::PROJECT_STATUS_OFFLINE => '已下线',
    ];

    /**
     * 返回允许的招商状态值
     * @return array
     */
    public static function getAllowedStatus()
    {
        return [self::PROJECT_STATUS_ONGOING, self::PROJECT_STATUS_OFFLINE];
    }

    /**
     * 请求招商项目列表
     * @param MerchantProjectFilter $filter
     * @return array
     */
    public function getProjectList(MerchantProjectFilter $filter)
    {
        return $this->sendRequest('/project/list', $filter->toAPIFilter());
    }

    /**
     * 自动完成补全招商项目名称
     * @param String $name
     * @return array
     */
    public function completeProjectByName($name)
    {
        $params = [
            'cond' => $name,
            'status' => implode(',', self::getAllowedStatus()),
        ];
        $data = $this->sendRequest('/data/sync/project', $params);
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'project_code' => $item['value'],
                'project_name' => html_entity_decode($item['text']),
            ];
        }
        return $result;
    }
}
