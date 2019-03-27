<?php

namespace App\Events;

use App\Models\Opportunity;

class OppStepChanged extends Event
{
    /**
     * @var Opportunity
     */
    public $opportunity;

    /**
     * @var int
     */
    public $fromStep;

    /**
     * @var int
     */
    public $toStep;

    /**
     * @var string
     */
    public $comment;

    /**
     * OppStepChanged constructor.
     * @param Opportunity $opportunity
     * @param int $fromStep
     * @param int $toStep
     * @param string $comment
     */
    public function __construct(Opportunity $opportunity, $fromStep, $toStep, $comment = '')
    {
        $this->opportunity = $opportunity;
        $this->fromStep = $fromStep;
        $this->toStep = $toStep;
        $this->comment = $comment;
    }
}
