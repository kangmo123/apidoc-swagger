<?php

namespace App\Library\Log;

use App\Library\Auth\User;
use Addev\Log\ProcessorDefault;

class Processor extends ProcessorDefault
{
    public function __invoke(array $record)
    {
        $user = new User(null);
        $user->setRequest($this->request);
        $record = parent::__invoke($record);
        $record['extra']['user'] = $user->getName() ?: '';
        $record['extra']['service_name'] = config('app.name');
        return $record;
    }
}