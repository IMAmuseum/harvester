<?php

namespace Imamuseum\Harvester\Models\Types;

use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    public function assets()
    {
        return $this->hasMany('Imamuseum\Harvester\Models\Asset');
    }
}
