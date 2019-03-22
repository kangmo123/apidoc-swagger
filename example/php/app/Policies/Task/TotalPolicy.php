<?php

namespace App\Policies\Task;

use App\Constant\TaskConstant;
use App\Library\User;

class TotalPolicy
{

    public function create(User $user)
    {
        $privileges = $user->getPrivileges();
        return in_array(TaskConstant::PRI_TASK_TOTAL_TASK, $privileges);
    }

    public function update(User $user)
    {
        $privileges = $user->getPrivileges();
        return in_array(TaskConstant::PRI_TASK_TOTAL_TASK, $privileges);
    }
}
