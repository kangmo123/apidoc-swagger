<?php

namespace App\Http\Resources;

use App\Library\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\Resource as BaseResource;

abstract class Resource extends BaseResource
{
    protected $withoutWrapping = false;

    public function with($request)
    {
        if ($this->withoutWrapping) {
            return [];
        }
        return ResourceCollection::WITH_DATA;
    }

    public function setWithoutWrapping($withoutWrapping)
    {
        $this->withoutWrapping = $withoutWrapping;
        return $this;
    }

    public static function item($resource, $withoutWrapping = false)
    {
        $item = new static($resource);
        $item->setWithoutWrapping($withoutWrapping);
        return $item;
    }

    public static function collection($resource, $withoutWrapping = false)
    {
        $collection = new ResourceCollection($resource, get_called_class());
        $collection->setWithoutWrapping($withoutWrapping);
        return $collection;
    }
}
