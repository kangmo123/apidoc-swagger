<?php

namespace App\Listeners;

use App\Models\Process;
use App\Events\OppStepChanged;

class RecordOppProcess extends BaseListener
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(OppStepChanged $event)
    {
        /** @var Process $process */
        if ($event->fromStep == $event->toStep) {
            $process = Process::findByOpportunityId($event->opportunity->opportunity_id)->latest()->first();
            $process->comment = $event->opportunity->step_comment;
        } else {
            $process = Process::buildByOpportunity($event->opportunity);
        }
        $process->save();
    }
}
