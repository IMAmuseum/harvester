<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Deaccession extends Model
{
    public function object()
    {
    	return $this->belongsTo('Imamuseum\Harvester\Models\Object');
	}
}
