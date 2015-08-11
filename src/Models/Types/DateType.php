<?php

namespace Imamuseum\Harvester\Models\Types;

use Illuminate\Database\Eloquent\Model;

class DateType extends Model
{
    public function dates()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Date');
    }
}
