<?php

namespace Imamuseum\Harvester\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $guarded = [];

    public function object()
    {
    	return $this->hasOne('Imamuseum\Harvester\Models\Object');
    }

    public function source()
    {
        return $this->hasOne('Imamuseum\Harvester\Models\Source', 'id', 'source_id');
    }

    public function type()
    {
        return $this->belongsTo('Imamuseum\Harvester\Models\Types\AssetType', 'asset_type_id');
    }
}
