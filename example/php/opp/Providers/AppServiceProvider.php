<?php

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use App\Library\Log\Processor;
use App\Library\Http\Guzzle\Handler;
use App\Http\Middleware\TraceMiddleware;
use Addev\Log\ProcessorInterface;
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
        $this->app->singleton('filesystem', function ($app) {
            return $app->loadComponent(
                'filesystems',
                \Illuminate\Filesystem\FilesystemServiceProvider::class,
                'filesystem'
            );
        });
    }

    public function registerMiddleware()
    {
        $this->app->middleware([
            TraceMiddleware::class,
        ]);

        $this->app->routeMiddleware([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    }
}
