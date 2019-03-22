<?php

namespace App\Http\Middleware;

use App\Constant\RevenueConst;
use App\Library\User;
use App\MicroService\ArchitectClient;
use Closure;
use Illuminate\Support\Facades\Auth;

class RevenueUserInfo
{

    /**
     * @var ArchitectClient
     */
    protected $architectClient;

    public function __construct(ArchitectClient $architectClient)
    {
        $this->architectClient = $architectClient;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var User $user
         */
        $user = Auth::user();
        if ($user->hasPrivilege(RevenueConst::PRI_OPERATOR_DIRECT) || $user->hasPrivilege(RevenueConst::PRI_OPERATOR_CHANNEL)) {
            $user->setRole(User::ROLE_OPERATOR);
            return $next($request);
        }
        $this->setForSale($user);
        return $next($request);
    }

    protected function setForSale(User $user)
    {
        $ret = $this->architectClient->getSaleInfo($user->getRtx(), 'sale_channel');
        $info = $ret['data'];
        $user->setSaleId($info['sale_id']);
        $user->setName($info['name']);
    }

}
