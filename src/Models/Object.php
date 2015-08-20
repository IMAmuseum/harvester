<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Object extends Model
{
    use \Imamuseum\Harvester\Traits\TransactionLogTrait;

    protected $guarded = [];

    public function actors()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Actor')->withPivot('sequence', 'role');
    }

    public function assets()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Asset');
    }

    public function terms()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Term');
    }

    public function texts()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Text');
    }

    public function locations()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Location');
    }

    public function dates()
    {
        return $this->belongsToMany('Imamuseum\Harvester\Models\Date');
    }

    public function deaccession()
    {
        return $this->hasOne('Imamuseum\Harvester\Models\Deaccession');
    }

}
