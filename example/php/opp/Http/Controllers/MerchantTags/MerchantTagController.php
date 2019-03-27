<?php

namespace App\Http\Controllers\MerchantTags;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\MerchantTags\MerchantTagFilter;
use App\Http\Resources\MerchantTags\MerchantTagResource;
use App\Repositories\MerchantTags\MerchantTagRepository;

class MerchantTagController extends Controller
{
    /**
     * @api {GET} /merchant-tags 招商标签列表
     * @apiGroup MerchantTags
     * @apiName GetMerchantTagList
     *
     * @apiUse MerchantTagFilter
     * @apiUse MerchantTagCollectionResource
     */
    /**
     * @param Request $request
     * @param MerchantTagRepository $repository
     * @return \App\Library\Http\Resources\Json\ResourceCollection
     */
    public function index(Request $request, MerchantTagRepository $repository)
    {
        return MerchantTagResource::collection($repository->getPaginator(new MerchantTagFilter($request)));
    }
}
