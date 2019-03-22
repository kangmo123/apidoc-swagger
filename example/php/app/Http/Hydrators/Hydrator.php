<?php

namespace App\Http\Hydrators;

use App\Exceptions\API\ValidationFailed;
use App\Models\Model;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

abstract class Hydrator
{
    /**
     * @var ValidatorFactory
     */
    protected $validatorFactory;

    protected $data = [];

    protected $model;

    public function __construct()
    {
        $this->validatorFactory = app(ValidatorFactory::class);
    }

    /**
     * @param array $data
     * @param Model $model
     * @return mixed
     */
    public function hydrate(array $data, Model $model)
    {
        $this->data = $data;
        $this->model = $model;
        if ($model->exists) {
            $this->validate($data, $this->getUpdateRules());
            return $this->hydrateForUpdate($data, $model);
        }
        $this->validate($data, $this->getCreateRules());
        return $this->hydrateForCreate($data, $model);
    }

    /**
     * @param array $data
     * @param array $rules
     */
    public function validate(array $data, array $rules)
    {
        $validator = $this->validatorFactory->make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationFailed(null, 0, $validator->errors());
        }
    }

    /**
     * @return array
     */
    abstract protected function getCreateRules();

    /**
     * @return array
     */
    abstract protected function getUpdateRules();

    /**
     * @param array $data
     * @param Model $model
     * @return Model
     */
    abstract protected function hydrateForCreate(array $data, Model $model);

    /**
     * @param array $data
     * @param Model $model
     * @return Model
     */
    abstract protected function hydrateForUpdate(array $data, Model $model);
}
