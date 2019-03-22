<?php

namespace App\Library;

use App\Library\Auth\User as AuthUser;

class User extends AuthUser
{
    const ROLE_ADMIN = -20;
    const ROLE_OPERATOR = -10;
    const ROLE_DEPARTMENT = 1;
    const ROLE_AREA = 10;
    const ROLE_DIRECTOR = 20;
    const ROLE_LEADER = 30;
    const ROLE_SALE = 40;

    /**
     * @var string 名称
     */
    protected $name = '';

    /**
     * @var string 销售id
     */
    protected $saleId = '';

    /**
     * @var int 角色
     */
    protected $role = null;

    /**
     * @var array 小组
     */
    protected $teams = [];

    /**
     * @var array admin系统中的权限
     */
    protected $privileges = [];

    /**
     * @var array admin系统权限map，key为code
     */
    protected $privilegeMap = [];

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSaleId()
    {
        return $this->saleId;
    }

    /**
     * @param string $saleId
     */
    public function setSaleId($saleId)
    {
        $this->saleId = $saleId;
    }

    /**
     * @return array
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * @param array $teams
     */
    public function setTeams(array $teams)
    {
        $this->teams = $teams;
    }

    /**
     * @return array
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * @param array $privileges
     */
    public function setPrivileges(array $privileges)
    {
        $this->privileges = $privileges;
        foreach ($privileges as $privilege) {
            $this->privilegeMap[$privilege['code']] = $privilege;
        }
    }

    /**
     * 用户是否有某个权限
     * @param $privilege
     * @return bool
     */
    public function hasPrivilege($privilege)
    {
        return array_key_exists($privilege, $this->privilegeMap);
    }

    /**
     * 给定一组权限，判断是否拥有
     * @param $privileges
     * @return array
     */
    public function checkPrivileges($privileges)
    {
        $ret = [];
        foreach ($privileges as $privilege) {
            if (array_key_exists($privilege, $this->privilegeMap)) {
                $ret[$privilege] = true;
            } else {
                $ret[$privilege] = false;
            }
        }
        return $ret;
    }

    public function isOperator()
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * 用户的级别 >= 总监
     * @return bool
     */
    public function gteDirector()
    {
        return $this->role <= self::ROLE_DIRECTOR;
    }

    /**
     * 用户的级别 >= 组长
     * @return bool
     */
    public function gteLeader()
    {
        return $this->role <= self::ROLE_LEADER;
    }

    /**
     * @return bool
     */
    public function isSale()
    {
        return $this->role === self::ROLE_SALE;
    }

    public function inTeam($teamId, $begin, $end)
    {
        foreach ($this->teams as $team) {
            if ($team['team_id'] === $teamId && $team['begin'] <= $end && $team['end'] >= $begin) {
                return true;
            }
        }
        return false;
    }
}
