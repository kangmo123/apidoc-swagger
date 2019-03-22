<?php

namespace App\Services\Task;

use App\Constant\TaskConstant;
use App\Exceptions\API\Forbidden;
use App\Library\User;
use App\MicroService\ArchitectClient;
use App\Services\BaseArchitectService;
use App\Services\Common\ConfigService;
use App\Services\Tree\Tree;
use App\Utils\Utils;

/**
 * Class ArchitectService
 * @package App\Services\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class ArchitectService extends BaseArchitectService
{
    public function __construct(ConfigService $configService, ArchitectClient $architectClient)
    {
        parent::__construct($configService, $architectClient);
    }

    public function getAdminArchitects()
    {
        $keys = TaskConstant::$operatorPrivileges;
        $data = $this->configService->getConfig($keys);
        $architects = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \RuntimeException("$key config does not exists.");
            }
            $architects = array_merge($architects, $data[$key]);
        }
        return $architects;
    }

    public function getOperatorArchitects(User $user)
    {
        $ret = $user->checkPrivileges(TaskConstant::$operatorPrivileges);
        $keys = [];
        foreach ($ret as $pri => $has) {
            if ($has) {
                $keys[] = $pri;
            }
        }
        $data = $this->configService->getConfig($keys);
        $architects = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \RuntimeException("$key config does not exists.");
            }
            $architects = array_merge($architects, $data[$key]);
        }
        return $architects;
    }

    public function getSaleAncestors($saleId, $begin, $end)
    {
        $params = [
            'sale_id' => $saleId,
            'begin_date' => $begin,
            'end_date' => $end
        ];
        $ret = $this->architectClient->getSaleAncestor($params);
        $data = isset($ret["data"]) ? $ret["data"][0] : [];
        foreach ($data as &$datum) {
            foreach ($datum['parents'] as &$parent) {
                $parent = Utils::removePrefixF($parent);
            }
            $datum = Utils::removePrefixF($datum);
        }
        unset($datum, $parent);
        return $data;
    }

    /**
     * @param User $user
     * @param $teamId
     * @param Tree $tree
     */
    public function checkTreeAuth(User $user, $teamId, Tree $tree)
    {
        if ($user->isOperator() || $user->isAdmin()) {
            return;
        }
        $noAuth = true;
        $teamList = $tree->bfs();
        foreach ($teamList as $teamNode) {
            if ($teamNode->getTeamId() != $teamId) {
                continue;
            }
            if ($teamNode->getLeader()->getSaleId() == $user->getSaleId()) {
                $noAuth = false;
                break;
            }
            $sales = $teamNode->getSales();
            foreach ($sales as $sale) {
                if ($sale->getSaleId() == $user->getSaleId()) {
                    $noAuth = false;
                    break;
                }
            }
        }
        if ($noAuth) {
            throw new Forbidden("{$user->getRtx()}没有{$teamId}任务操作的权限");
        }
    }
}
