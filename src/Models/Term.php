<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    public $timestamps = false;

    public function objects()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Object');
    }

    public function type()
    {
        return $this->belongsTo('Imamuseum\Harvester\Models\Types\TermType', 'term_type_id');
    }
}
