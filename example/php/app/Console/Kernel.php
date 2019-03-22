<?php

namespace App\Console;

use Laravel\Lumen\Application;
use App\Library\Command\CommandReporter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\CommandFinished;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /***
     * Kernel constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        /** @var Dispatcher $events */
        $events = app('events');
        /** @var CommandReporter $reporter */
        $reporter = app(CommandReporter::class);
        $events->listen(CommandStarting::class, function(CommandStarting $event) use ($reporter) {
            $reporter->starting($event);
        });
        $events->listen(CommandFinished::class, function(CommandFinished $event) use ($reporter) {
            $reporter->finished($event);
        });
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //这里用于定义定时执行的各种任务，请参考https://laravel.com/docs/5.5/scheduling#defining-schedules
    }
}
