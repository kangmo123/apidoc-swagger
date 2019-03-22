<?php

namespace App\Library\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Http\Request;
use Laravel\Lumen\Auth\Authorizable;

class User implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * @var string 用户rtx
     */
    protected $rtx;

    /**
     * @var string 模拟用户的rtx
     */
    protected $mockRtx;

    /**
     * @var Request
     */
    protected $request;

    public function __construct($rtx = null)
    {
        $this->rtx = $rtx;
    }

    /**
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
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
        if ($this->rtx) {
            return $this->rtx;
        }
        if ($this->request) {
            return $this->request->header($this->getAuthIdentifierName());
        }
        return null;
    }

    public function getMockIdentifierName()
    {
        return 'X-Mock-Staff-Name';
    }

    /**
     * 获取用户名称
     * @param bool $mock
     * @return string
     */
    public function getRtx($mock = false)
    {
        if ($mock) {
            return $this->getMockRtx();
        }
        return $this->getAuthIdentifier();
    }

    /**
     * 获取模拟用户的名称
     * @return string
     */
    protected function getMockRtx()
    {
        if ($this->mockRtx) {
            return $this->mockRtx;
        }
        if ($this->request->hasHeader($this->getMockIdentifierName())) {
            return $this->request->header($this->getMockIdentifierName());
        }
        return $this->getAuthIdentifier();
    }

    /**
     * @param string $rtx
     */
    public function setRtx($rtx)
    {
        $this->rtx = $rtx;
    }

    /**
     * @param string $mockRtx
     */
    public function setMockRtx($mockRtx)
    {
        $this->mockRtx = $mockRtx;
    }

}
