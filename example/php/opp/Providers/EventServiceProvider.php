<?php

namespace App\Providers;

use App\Events\OppStepChanged;
use App\Listeners\RecordOppProcess;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OppStepChanged::class => [
            RecordOppProcess::class,
        ],
    ];
}
