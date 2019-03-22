<?php

namespace App\Http\Middleware;

use App\Constant\MerchantConstant;
use App\Library\User;
use App\MicroService\ArchitectClient;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MerchantUserInfo
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
        if ($user->hasPrivilege(MerchantConstant::PRI_MERCHANT_REVENUE_ADMIN)) {
            Log::info($user->getRtx() . " is merchant revenue admin");
            $user->setRole(User::ROLE_ADMIN);
            return $next($request);
        }
        Log::info($user->getRtx() . " is sale for merchant revenue");
        $this->setForSale($user);
        return $next($request);
    }

    protected function setForSale(User $user)
    {
        $ret = $this->architectClient->getSaleInfo($user->getRtx());
        $info = $ret['data'];
        $user->setSaleId($info['sale_id']);
        $user->setName($info['name']);
    }
}
