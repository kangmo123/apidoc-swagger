<?php

namespace App\Http\Middleware;

use App\Constant\TaskConstant;
use App\Library\User;
use App\MicroService\ArchitectClient;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskUserInfo
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
        if ($user->hasPrivilege(TaskConstant::PRI_TASK_ADMIN)) {
            Log::info("{$user->getRtx()} is task admin");
            $user->setRole(User::ROLE_ADMIN);
            return $next($request);
        }
        $ret = $user->checkPrivileges(TaskConstant::$operatorPrivileges);
        foreach ($ret as $pri => $has) {
            if ($has) {
                $user->setRole(User::ROLE_OPERATOR);
                Log::info("{$user->getRtx()} is operator. pri: $pri");
                $user->setRole(User::ROLE_OPERATOR);
                return $next($request);
            }
        }
        $this->initSale($user);
        return $next($request);
    }

    protected function initSale(User $user)
    {
        $ret = $this->architectClient->getSaleInfo($user->getRtx());
        $info = $ret['data'];
        $user->setSaleId($info['sale_id']);
        $user->setName($info['name']);
    }
}
