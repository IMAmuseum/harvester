<?php

namespace Imamuseum\Harvester\Models\Types;

use Illuminate\Database\Eloquent\Model;

class TermType extends Model
{
    public function terms()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Term');
    }
}
