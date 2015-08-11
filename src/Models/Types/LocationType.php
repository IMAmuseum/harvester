<?php

namespace Imamuseum\Harvester\Models\Types;

use Illuminate\Database\Eloquent\Model;

class LocationType extends Model
{
    public function locations()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Location');
    }
}
