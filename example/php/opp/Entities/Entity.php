<?php

namespace App\Entities;

use Carbon\Carbon;

/**
 * Class Entity
 * The base class for items such as opportunities, forecasts & details.
 * This is not a database model in itself but extended.
 *
 * @property integer $id
 * @property string $opportunity_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $created_by
 * @property string $updated_by
 * @property boolean $is_del
 *
 * @package App\Entities
 */
class Entity
{
    /**
     * @var float - Multiplier for search indexing.
     */
    public $searchFactor = 1.0;

    /**
     * Get the morph class for this model.
     * Set here since, due to folder changes, the namespace used
     * in the database no longer matches the class namespace.
     * @return string
     */
    public function getMorphClass()
    {
        // Auth::user()->getAuthIdentifier();
        return 'App\\Entity';
    }

    /**
     * Compares this entity to another given entity.
     * Matches by comparing class and id.
     * @param $entity
     * @return bool
     */
    public function matches($entity)
    {
        return [get_class($this), $this->id] === [get_class($entity), $entity->id];
    }

    /**
     * Allows checking of the exact class, Used to check entity type.
     * Cleaner method for is_a.
     * @param $type
     * @return bool
     */
    public static function isA($type)
    {
        return static::getType() === strtolower($type);
    }

    /**
     * Get entity type.
     * @return mixed
     */
    public static function getType()
    {
        return strtolower(static::getClassName());
    }

    /**
     * Get an instance of an entity of the given type.
     * @param $type
     * @return Entity
     */
    public static function getEntityInstance($type)
    {
        $types     = ['Opportunity', 'Forecast', 'Detail'];
        $className = str_replace([' ', '-', '_'], '', ucwords($type));
        if (!in_array($className, $types)) {
            return null;
        }

        return app('App\\Entities\\' . $className);
    }
}
