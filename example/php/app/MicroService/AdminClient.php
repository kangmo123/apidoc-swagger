<?php

namespace App\MicroService;

use App\Services\Common\AdminService;

/**
 * Class ArchitectClient
 * @package App\MicroService\Architect
 * @author caseycheng <caseycheng@tencent.com>
 *
 */
class AdminClient extends Client
{

    protected $methods = [
        "getChildren" => [
            "method" => "get",         //默认是get
            "uri" => "/v1/sales/subordinates",
            "replacement" => false,     //是否需要替换占位符
        ],
    ];

    protected function getMethods()
    {
        return $this->methods;
    }

    protected function getServiceName()
    {
        return "admin.service";
    }

    public function get($rtx)
    {
        /**
         * @var AdminService $service
         */
        $service = app()->make(AdminService::class);
        $privileges = $service->getUserPrivilege($rtx);
        return $privileges;
    }

}
