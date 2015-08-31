<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public $timestamps = false;

    public function objects()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Object');
    }

    public function actors()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Actor');
    }

    public function type()
    {
        return $this->belongsTo('Imamuseum\Harvester\Models\Types\LocationType', 'location_type_id');
    }
}
