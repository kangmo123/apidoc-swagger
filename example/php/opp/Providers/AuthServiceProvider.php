<?php

namespace App\Providers;

use App\Library\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Exceptions\API\Unauthorized;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        //
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        /** @var AuthManager $auth */
        $auth = $this->app['auth'];
        $auth->viaRequest('api', function (Request $request) {
            $user = new User(null);
            $user->setRequest($request);
            if (!$user->getName()) {
                throw new Unauthorized(0, "请传递{$user->getAuthIdentifierName()}的Header来表明身份");
            }
            return $user;
        });
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    protected function registerPolicies()
    {
        foreach ($this->policies as $key => $value) {
            $this->app[Gate::class]->policy($key, $value);
        }
    }
}
