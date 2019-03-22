<?php

namespace App\Library\Log;

use Addev\Log\ProcessorDefault;
use App\Library\Auth\User;

class Processor extends ProcessorDefault
{
    public function __invoke(array $record)
    {
        $user = new User(null);
        $user->setRequest($this->request);
        $record = parent::__invoke($record);
        $record['extra']['user'] = $user->getRtx() ?: '';
        $record['extra']['real_user'] = $user->getRtx(true) ?: '';
        $record['extra']['service_name'] = config('app.name');
        return $record;
    }
}
