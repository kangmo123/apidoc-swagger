<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->configure('app');
        $this->app->configure('log');
        $this->app->configure('mail');
        $this->app->configure('proxy');
        $this->app->configure('services');
    }

    /**
     * Register All Custom Config Files
     *
     * @return void
     */
    public function boot()
    {
        /** @var \Illuminate\Http\Request $request */
        $request = $this->app->make('request');
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');
        $proxies = array_map('trim', explode(',', $config->get('proxy.trusted_proxies')));
        $request->setTrustedProxies($proxies);
    }
}
