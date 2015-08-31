<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Date extends Model
{
    public $timestamps = false;

    protected $dates = ['date_at'];

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
        return $this->belongsTo('Imamuseum\Harvester\Models\Types\DateType', 'date_type_id');
    }
}
