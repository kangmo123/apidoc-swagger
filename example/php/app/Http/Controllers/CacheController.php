<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class CacheController
 * @package App\Http\Controllers\Task
 * @author caseycheng <caseycheng@tencent.com>
 */
class CacheController extends Controller
{

    public function clear()
    {
        $user = Auth::user();
        Log::info($user->getRtx() . " has cleared all cache");
        CacheTags()->flush();
        return $this->success(['clear by' => $user->getRtx(true)]);
    }

}
