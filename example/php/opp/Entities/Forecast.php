<?php

namespace App\Entities;

class ForecastEnities extends Entity
{
    protected $fillable = ['year', 'q', 'begin', 'end'];

    /**
     * Get the morph class for this model.
     * @return string
     */
    public function getMorphClass()
    {
        return 'BookStack\\Forecast';
    }

    /**
     * Get the opportunity this forecast is within.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function oportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Get the details that this forecast contains.
     * @param string $dir
     * @return mixed
     */
    public function details($dir = 'ASC')
    {
        return $this->hasMany(Detail::class)->orderBy('id', $dir);
    }

    /**
     * Check if this forecast has a detail.
     * @return bool
     */
    public function hasDetail()
    {
        return $this->details()->count() > 0;
    }
}
