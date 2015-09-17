<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    protected $guarded = [];

    public function objects()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Object')->withPivot('sequence', 'role');
    }

    public function locations()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Location');
    }

    public function dates()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Date');
    }
}
