<?php

namespace Imamuseum\Harvester\Models\Types;

use Illuminate\Database\Eloquent\Model;

class TextType extends Model
{
    public function texts()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Text');
    }

}
