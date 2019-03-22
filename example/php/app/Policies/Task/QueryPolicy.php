<?php

namespace App\Policies\Task;

use App\Constant\TaskConstant;
use App\Library\User;

class QueryPolicy
{
    /**todo：替换
     * @param User $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->hasPrivilege(TaskConstant::PRI_TASK_QUERY_TASK);
    }
}
