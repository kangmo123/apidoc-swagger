<?php

namespace App\Repositories;

use App\Models\Option;
use App\Models\OptionsRelation;
use App\Services\ConstDef\OptionDef;

class OptionDatabaseRepository implements ModelRepository
{
    /**
     * @param $criteria
     *
     * @return int
     */
    public function getCount($criteria)
    {
        $query = Option::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        $count = $query->count();
        return $count;
    }

    /**
     * @param $criteria
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function getOneModel($criteria)
    {
        $query = Option::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        $option = $query->first();
        return $option;
    }

    /**
     * @param $criteria
     * @param $limit
     * @param $offset
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getModels($criteria, $limit, $offset)
    {
        $query = Option::query();
        foreach ($criteria as $key => $val) {
            $query->where($key, $val);
        }
        if (isset($limit)) {
            $query->limit($limit);
        }
        if (isset($offset)) {
            $query->offset($offset);
        }
        $options = $query->get();
        return $options;
    }

    /**
     * @param OptionFilter $filter
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginator(OptionFilter $filter)
    {
        $query = Option::query();
        if ($filter->getType()) {
            $query->where('type', $filter->getType());
        }
        if ($filter->getKeyword()) {
            $query->where('value', 'LIKE', "%{$filter->getKeyword()}%");
        }

        return $query->paginate($filter->getPerPage(), ['*'], 'page', $filter->getPage());
    }

    /**
     * 获取所有的选项名称，按类型和键值排列数组
     * ['type'=>['key'=>['text','value']]].
     *
     * @return array
     */
    public function getOptionsValue()
    {
        $data = Option::query()->get()->toArray();
        $response = [];
        foreach ($data as $item) {
            $response[$item['type']][$item['key']] = [
                'value' => $item['key'],
                'text'  => $item['value'],
            ];
        }
        return $response;
    }

    /**
     * 按关联层级查询配置项.
     *
     * @param OptionFilter $filter
     *
     * @return array
     */
    public function getGradedOptions(OptionFilter $filter)
    {
        $query = OptionsRelation::query();
        $query->select(
            [
                'platform',
                'cooperation',
                'product',
                'form',
            ]
        );
        $relations = $query->get()->toArray();

        $options_value = $this->getOptionsValue();

        $response = [];
        $mark = [];
        foreach ($relations as $item) {
            $platform = $item['platform'];
            $cooperation = $item['cooperation'];
            $product = $item['product'];
            $form = $item['form'];

            if (!isset($mark[$platform . '-' . $cooperation])) {
                $response[$platform]['cooperation'][$cooperation] = $options_value[OptionDef::OPTION_COO_FORMS][$cooperation];
                $mark[$platform . '-' . $cooperation] = 1;
            }

            if ($product && !isset($mark[$platform . '-' . $cooperation . '-' . $product])) {
                $response[$platform]['cooperation'][$cooperation]['products'][$product] = $options_value[OptionDef::OPTION_AD_PRODUCTS][$product];
                $mark[$platform . '-' . $cooperation . '-' . $product] = 1;
            }

            if ($form) {
                $response[$platform]['cooperation'][$cooperation]['products'][$product]['resource'][] = $options_value[OptionDef::OPTION_FORMS][$form];
            }
        }

        // 取【投放平台】层级关系
        $platforms = $this->getOptionByType(OptionDef::OPTION_INV_PLATFORMS);
        $platforms = $this->formatOutput($platforms);
        foreach ($platforms as &$platform) {
            // 删除键值
            $products = $response[$platform['value']]['cooperation'][OptionDef::COOPERATION_PRODUCT]['products'] ?? null;
            if (isset($products)) {
                $response[$platform['value']]['cooperation'][OptionDef::COOPERATION_PRODUCT]['products'] = array_values($products);
            }

            // 前端要求额外字段
            $platform['label'] = $platform['text'];

            $platform['cooperation'] = $response[$platform['value']]['cooperation'] ?? [];

            if (isset($platform['children'])) {
                foreach ($platform['children'] as &$item) {
                    // 删除键值
                    $products = $response[$item['value']]['cooperation'][OptionDef::COOPERATION_PRODUCT]['products'] ?? null;
                    if (isset($products)) {
                        $response[$item['value']]['cooperation'][OptionDef::COOPERATION_PRODUCT]['products'] = array_values($products);
                    }

                    // 前端要求额外字段
                    $item['label'] = $item['text'];

                    $item['cooperation'] = array_values($response[$item['value']]['cooperation']);
                }
            }
        }

        return array_values($platforms);
    }

    /**
     * 搜索配置项.
     *
     * @param OptionFilter $filter
     */
    public function searchOption(OptionFilter $filter)
    {
        $response = [];
        foreach ($filter->getType() as $type) {
            $response[$type] = $this->formatOutput($this->getOptionByType($type));
        }
        return $response;
    }

    /**
     * 根据类型获取配置项.
     *
     * @param int $type
     */
    private function getOptionByType($type)
    {
        return Option::query()->where('type', $type)->get()->toArray();
    }

    /**
     * 按父级关系格式化输出配置项.
     *
     * @param $data
     *
     * @return array
     */
    private function formatOutput($data)
    {
        $response = [];
        foreach ($data as $item) {
            if ($item['parent']) {
                $response[$item['parent']]['children'][] = [
                    'value' => $item['key'],
                    'text'  => $item['value'],
                ];
            } else {
                $response[$item['key']]['value'] = $item['key'];
                $response[$item['key']]['text'] = $item['value'];
            }
        }
        return array_values($response);
    }
}
