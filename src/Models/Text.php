<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    public function object()
    {
        return $this->belongsTo('Imamuseum\Harvester\Models\Object');
    }

    public function type()
    {
        return $this->belongsTo('Imamuseum\Harvester\Models\Types\TextType', 'text_type_id');
    }
}
