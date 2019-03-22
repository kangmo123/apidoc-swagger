<?php

namespace App\Http\Resources;

use App\Library\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\Resource as BaseResource;

abstract class Resource extends BaseResource
{
    public function with($request)
    {
        return ResourceCollection::WITH_DATA;
    }

    public static function item($resource)
    {
        return new static($resource);
    }

    public static function collection($resource)
    {
        return new ResourceCollection($resource, get_called_class());
    }
}
