<?php

namespace App\Entities;

/**
 * Class EntityProvider
 *
 * Provides access to the core entity models.
 * Wrapped up in this provider since they are often used together
 * so this is a neater alternative to injecting all in individually.
 *
 * @package App\Entities
 */
class EntityProvider
{
    /**
     * @var Opportunity
     */
    public $opportunity;

    /**
     * @var Forecast
     */
    public $forecast;

    /**
     * @var Detail
     */
    public $detail;

    /**
     * EntityProvider constructor.
     * @param Opportunity $opportunity
     * @param Forecast $forecast
     * @param Detail $detail
     */
    public function __construct(
        Opportunity $opportunity,
        Forecast $forecast,
        Detail $detail
    ) {
        $this->opportunity          = $opportunity;
        $this->forecast             = $forecast;
        $this->detail               = $detail;
    }

    /**
     * Fetch all core entity types as an associated array
     * with their basic names as the keys.
     * @return Entity[]
     */
    public function all()
    {
        return [
            'opportunity'      => $this->opportunity,
            'forecast'         => $this->forecast,
            'detail'           => $this->detail,
        ];
    }

    /**
     * Get an entity instance by it's basic name.
     * @param string $type
     * @return Entity
     */
    public function get(string $type)
    {
        $type = strtolower($type);
        return $this->all()[$type];
    }
}
