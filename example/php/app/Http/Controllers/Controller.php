<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{

    public function success($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data) && !($data instanceof \JsonSerializable)) {
            $data = [$data];
        }
        $ret = [
            "code" => 0,
            "msg" => "OK",
            "data" => $data
        ];
        return response()->json($ret);
    }

}
