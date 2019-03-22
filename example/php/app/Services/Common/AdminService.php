<?php

namespace App\Services\Common;

use App\Constant\TaskConstant;
use App\Exceptions\API\NotFound;
use Illuminate\Support\Facades\DB;

class AdminService
{
    /**
     * 获取用户的crm的权限
     * @param $rtx
     * @return array
     */
    public function getUserPrivilege($rtx)
    {
        $cond = [
            'Fuser_id' => $rtx,
            'Fenable' => 'Y'
        ];
        //获取用户
        $user = DB::table('t_user')->where($cond)->first();
        if (empty($user)) {
            throw new NotFound("用户{$rtx}在Admin系统中不存在，请先配置");
        }
        $roles = trim($user->Froles, ';');
        $roleArr = explode(';', $roles);
        if (empty($roleArr)) {
            return [];
        }
        //获取角色关联的权限id
        $rolePrivileges = DB::table('t_role_privileges_relation')
            ->where('Flicense_type', 'Y')
            ->whereIn('Frole_id', $roleArr)
            ->get();
        $privilegeArr = [];
        foreach ($rolePrivileges as $rolePrivilege) {
            $privilegeArr[] = $rolePrivilege->Fprivilege_id;
        }
        $rootPrivilege = $this->getCrmRootPrivilege();
        $path = $rootPrivilege->Fpath;

        //获取全部的权限
        $privileges = DB::table('t_privilege')
            ->where('Fenable', 'Y')
            ->whereIn('Fprivilege_id', $privilegeArr)
            ->where('Fpath', 'like', $path . '%')
            ->get();
        $ret = [];
        foreach ($privileges as $privilege) {
            $ret[] = [
                'privilege_id' => $privilege->Fprivilege_id,
                'father_id' => $privilege->Ffather_id,
                'code' => $privilege->Fcode,
                'name' => $privilege->Fname,
                'level' => $privilege->Flevel,
                'path' => $privilege->Fpath,
            ];
        }
        return $ret;
    }

    /**
     * 获取crm的根权限
     * @return mixed
     */
    protected function getCrmRootPrivilege()
    {
        $cond = [
            'Fcode' => TaskConstant::PRI_CRM,
            'Fenable' => 'Y'
        ];
        $privilege = DB::table('t_privilege')->where($cond)->first();
        return $privilege;
    }
}
