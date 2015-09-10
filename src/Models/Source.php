<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use \Imamuseum\Harvester\Traits\TransactionLogTrait;

    protected $guarded = [];

    public function object()
    {
    	return $this->hasOne('Imamuseum\Harvester\Models\Object');
    }

    public function assets()
    {
    	return $this->hasMany('Imamuseum\Harvester\Models\Asset');
    }

}
