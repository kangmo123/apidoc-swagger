<?php

namespace App\Providers;

use Addev\Log\ProcessorInterface;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ClientOrderUserInfo;
use App\Http\Middleware\MerchantUserInfo;
use App\Http\Middleware\RevenueUserInfo;
use App\Http\Middleware\TaskUserInfo;
use App\Http\Middleware\TraceMiddleware;
use App\Library\Http\Guzzle\Handler;
use App\Library\Log\Processor;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        DB::listen(function ($query) {
            $sql = $query->sql;
            $bindings = $query->bindings;
            $time = $query->time;
            $sql = str_replace("?", "%s", $sql);
            $msg = "time: $time, sql: " . vsprintf($sql, $bindings);
            Log::debug($msg);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBinding();
        $this->registerMiddleware();
    }

    public function registerBinding()
    {
        $this->app->bind(ClientInterface::class, function () {
            return new Client([
                'connect_timeout' => 2,
                'timeout' => 5,
                'handler' => Handler::getMicroServiceHandler(),
            ]);
        });
        $this->app->alias(ClientInterface::class, Client::class);
        $this->app->bind(ProcessorInterface::class, Processor::class);
    }

    public function registerMiddleware()
    {
        $this->app->middleware([
            TraceMiddleware::class,
        ]);
        $this->app->routeMiddleware([
            'auth' => Authenticate::class,
            'admin' => Admin::class,
            'task_user_info' => TaskUserInfo::class,
            'revenue_user_info' => RevenueUserInfo::class,
            'client_order_user_info' => ClientOrderUserInfo::class,
            'merchant_user_info' => MerchantUserInfo::class,
        ]);
    }
}
