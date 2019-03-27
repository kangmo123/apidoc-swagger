<?php

namespace App\Entities;

class DetailEnities extends Entity
{
    protected $fillable = ['year', 'q', 'platform', 'cooperation_type', 'business_project_id', 'business_project', 'ad_product_id', 'ad_product', 'resource_id', 'resource_name'];

    /**
     * Get the morph class for this model.
     * @return string
     */
    public function getMorphClass()
    {
        return 'BookStack\\Detail';
    }

    /**
     * Get the opportunity this detail sits in.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Get the parent item
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->forecast();
    }

    public function forecast()
    {
        return $this->belongsTo(Forecast::class);
    }
}
