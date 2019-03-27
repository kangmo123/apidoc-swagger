<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\OptionDatabaseRepository;
use App\Repositories\OptionFilter;
use Illuminate\Http\Response;

class OptionController extends Controller
{
    /**
     * @api      {GET} /options 获取全部商机配置项
     * @apiGroup Option
     * @apiName  GetOptions
     *
     * @param Request                  $request
     * @param OptionDatabaseRepository $repository
     *
     * @return \App\Library\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, OptionDatabaseRepository $repository)
    {
        try {
            $filter = new OptionFilter($request);
            $response = $repository->getGradedOptions($filter);

            return new Response([
                'code'        => 0,
                'msg'         => 'OK',
                'data'        => $response,
            ]);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }

    /**
     * @api      {GET} /options/search 搜索配置项
     * @apiGroup Option
     * @apiName  SearchOptions
     *
     * @apiParam {Number} type_id = 1,2 配置项类型编码，多种类型用逗号分隔
     *
     * @param Request                  $request
     * @param OptionDatabaseRepository $repository
     *
     * @return \App\Library\Http\Resources\Json\ResourceCollection|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(Request $request, OptionDatabaseRepository $repository)
    {
        try {
            $filter = new OptionFilter($request);

            $response = $repository->searchOption($filter);

            return new Response([
                'code'        => 0,
                'msg'         => 'OK',
                'data'        => $response,
            ]);
        } catch (\Exception $e) {
            return $this->dealException($e);
        }
    }
}
