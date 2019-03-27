<?php

namespace App\Http\Request;

class CheckOppNameExistRequest extends BaseRequest
{
    public static $fields = [
        'opportunity_id',
        'opp_name',
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'opp_name'         => 'required|string|max:250',
            'opportunity_id'   => 'nullable|string|max:36',
        ];
    }

    public function getOpportunityId()
    {
        return $this->input('opportunity_id');
    }

    public function getOppName()
    {
        return $this->input('opp_name');
    }
}
