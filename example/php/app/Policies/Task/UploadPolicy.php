<?php

namespace App\Policies\Task;

use App\Constant\TaskConstant;
use App\Library\User;

class UploadPolicy
{
    /**todo：替换
     * @param User $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->hasPrivilege(TaskConstant::PRI_TASK_UPLOAD_TASK);
    }
}
