<?php

namespace App\Library\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * @var string 用户名称
     */
    protected $name;

    /**
     * @var Request
     */
    protected $request;

    /**
     * User constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 获取用户标识名称
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'X-Staff-Name';
    }

    /**
     * 获取用户标识
     * @return string
     */
    public function getAuthIdentifier()
    {
        if ($this->name) {
            return $this->name;
        }
        if ($this->request) {
            return $this->request->header($this->getAuthIdentifierName());
        }
        return null;
    }

    /**
     * 获取用户名称
     * @return string
     */
    public function getName()
    {
        return $this->getAuthIdentifier();
    }
}