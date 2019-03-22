<?php

namespace App\Http\Middleware;

use App\Library\User;
use App\MicroService\AdminClient;
use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{

    /**
     * @var AdminClient
     */
    protected $client;

    public function __construct(AdminClient $client)
    {
        $this->client = $client;
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
        $privileges = $this->getPrivileges($user);
        $user->setPrivileges($privileges);
        return $next($request);
    }

    protected function getPrivileges(User $user)
    {
        $ret = $this->client->get($user->getRtx());
        return $ret;
    }

}
