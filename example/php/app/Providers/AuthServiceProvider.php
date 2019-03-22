<?php

namespace App\Providers;

use App\Constant\TaskConstant;
use App\Exceptions\API\Unauthorized;
use App\Library\User;
use App\Policies\Task\AssignmentPolicy;
use App\Policies\Task\QueryPolicy;
use App\Policies\Task\UploadPolicy;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
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
            $user = new User();
            $user->setRequest($request);
            if (!$user->getAuthIdentifier()) {
                throw new Unauthorized("请传递{$user->getAuthIdentifierName()}的Header来表明身份");
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
        Gate::define(TaskConstant::POLICY_TASK_QUERY, QueryPolicy::class . "@index");
        Gate::define(TaskConstant::POLICY_TASK_UPLOAD, UploadPolicy::class . "@index");
    }
}
