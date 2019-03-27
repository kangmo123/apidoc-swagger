<?php

namespace App\Repositories\MerchantProjects;

class MerchantProject
{
    /**
     * @var string 招商项目编码
     */
    public $project_code;

    /**
     * @var string 招商项目名称
     */
    public $project_name;

    /**
     * 通过API参数来构建对象
     * @param array $params
     * @return self
     */
    public static function buildFromAPI(array $params)
    {
        $model = new MerchantProject();
        $model->project_code = $params['Fproject_code'];
        $model->project_name = html_entity_decode($params['Fproject_name']);
        return $model;
    }

    /**
     * 通过API参数来构建对象
     * @param array $params
     * @return self
     */
    public static function buildFromES(array $params)
    {
        //TODO 通过ES参数来构建对象
        return new MerchantProject();
    }
}
