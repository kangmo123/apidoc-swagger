<?php

namespace App\Library\Command;

use App\Library\Monitor\Monitor;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\InputInterface;

class CommandReporter
{
    /** @var string */
    const REPORT_KEY = 'command-statics';

    /** @var string */
    const BINARY_PREFIX = 'php artisan ';

    /** @var array */
    const PREDEFINED_COMMANDS = [
        'help',
        'list',
        'migrate',
        'tinker',
        'auth:clear-resets',
        'cache:clear',
        'cache:forget',
        'cache:table',
        'db:seed',
        'make:migration',
        'make:seeder',
        'migrate:install',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'migrate:status',
        'queue:failed',
        'queue:failed-table',
        'queue:flush',
        'queue:forget',
        'queue:listen',
        'queue:restart',
        'queue:retry',
        'queue:table',
        'queue:work',
        'schedule:run',
    ];

    /** @var array */
    protected static $RUNNING_STATICS = [];

    /** @var Monitor */
    protected $monitor;

    /**
     * CommandReporter constructor.
     * @param Monitor $monitor
     */
    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * @param CommandStarting $event
     */
    public function starting(CommandStarting $event)
    {
        if (!$this->shouldProcess($event->command)) {
            return;
        }
        $command = $this->buildCommand($event->input);
        self::$RUNNING_STATICS[$command]['starting_at'] = microtime(true);
    }

    /**
     * @param CommandFinished $event
     */
    public function finished(CommandFinished $event)
    {
        if (!$this->shouldProcess($event->command)) {
            return;
        }
        $command = $this->buildCommand($event->input);
        self::$RUNNING_STATICS[$command]['finished_at'] = microtime(true);
        //开始上报
        $duration = round((self::$RUNNING_STATICS[$command]['finished_at'] - self::$RUNNING_STATICS[$command]['starting_at']) * 1000);
        $this->report($event->command, $command, $duration, $event->exitCode);
    }

    /**
     * @param $commandName
     * @return bool
     */
    protected function shouldProcess($commandName)
    {
        if (empty($commandName)) {
            return false;
        }
        if (in_array($commandName, self::PREDEFINED_COMMANDS)) {
            return false;
        }
        return true;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function buildCommand(InputInterface $input)
    {
        return self::BINARY_PREFIX . str_replace(['"', "'"], '', $input);
    }

    /**
     * @param $commandName
     * @param $command
     * @param $duration
     * @param $exitCode
     */
    protected function report($commandName, $command, $duration, $exitCode)
    {
        $this->monitor->report(self::REPORT_KEY, $duration, [
            'command_name' => $commandName,
            'command' => $command,
            'exit_code' => $exitCode,
        ]);
    }
}
