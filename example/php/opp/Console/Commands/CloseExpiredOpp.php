<?php

namespace App\Console\Commands;

use App\Library\Auth\User;
use App\Models\Opportunity;
use Illuminate\Console\Command;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\Auth;
use App\Services\ConstDef\OpportunityDef;

class CloseExpiredOpp extends Command
{
    protected $signature = 'opp:close-expired';

    protected $description = 'Close expired opportunities';

    /**
     * CloseExpiredOpp constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        ini_set('memory_limit', '512M');
        Auth::setUser(new User('CMD'));
        /** @var OpportunityService $oppService */
        $oppService = app(OpportunityService::class);
        $data = $oppService->getExpired();
        foreach ($data as $opp) {
            /** @var Opportunity $opp */
            $this->info("Close Opp: " . $opp->opportunity_id);
            $opp->changeStepTo(OpportunityDef::STEP_LOSE, '商机过期自动失单');
        }
        $this->info("Total Expired: " . count($data));
    }
}
