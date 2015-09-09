<?php

namespace Imamuseum\Harvester\Commands;

use App\Jobs\Job;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jeremytubbs\Deepzoom\DeepzoomFactory;
use Intervention\Image\ImageManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Models\Types\AssetType;
use Imamuseum\Harvester\Models\Asset;


class HarvestImages extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;

    protected $object_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($object_id)
    {
        $this->object_id = $object_id;
        $this->sizes = config('harvester.image.sizes');
        $this->protected = config('harvester.image.protected');
        $this->deepzoom = null;
        $this->filesystem = new Filesystem(new Local(public_path()));
        $this->imageManager = new ImageManager(['driver' => config('harvester.image.driver')]);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->deepzoom = DeepzoomFactory::create([
            'path' => public_path('images/'.$this->object_id),
            'driver' => config('harvester.image.driver')
        ]);

        // get object
        $object = Object::with('assets', 'assets.type')->findOrFail($this->object_id);

        // delete directory if it exists
        if ($this->filesystem->has('images/'.$object->id)) {
            $this->filesystem->deleteDir('images/'.$object->id);
            $this->deleteAssetRecords($object);
        }

        foreach ($object->assets as $asset) {
            if($asset->type->asset_type_name == 'source') {
                // if object has proper rights
                if ($object->can_zoom == 1 && $object->can_download == 1) {
                    // get result back form deepzoom
                    $result = $this->deepzoom->makeTiles($asset->asset_file_uri, 'dzi'.$asset->asset_sequence, $asset->asset_sequence);
                    foreach ($result['data'] as $key => $value) {
                        $asset_type_id = AssetType::where('asset_type_name', '=', strtolower($key))->pluck('id');
                        if ($asset_type_id) {
                            $imgPath = 'images/'.$object->id.'/'.$value;
                            $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                        }
                    }

                    foreach ($this->sizes as $key => $value) {
                        $asset_type_id = AssetType::where('asset_type_name', '=', $key)->pluck('id');
                        $img = $this->imageManager->make($asset->asset_file_uri);
                        // prevent possible upsizing
                        $img->resize($value, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $img->encode('jpg');
                        $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'/'.$asset->asset_sequence.'_'.$key.'.jpg';
                        $this->filesystem->put($imgPath, $img);
                        $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                    }
                // generate the sizes below the protected width
                } else {
                    foreach ($this->sizes as $key => $value) {
                        if ($value <= $this->protected['width']) {
                            $asset_type_id = AssetType::where('asset_type_name', '=', $key)->pluck('id');
                            $img = $this->imageManager->make($asset->asset_file_uri);
                            // prevent possible upsizing
                            $img->resize($value, null, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
                            $img->encode('jpg');
                            $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'/'.$asset->asset_sequence.'_'.$key.'.jpg';
                            $this->filesystem->put($imgPath, $img);
                            $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                        }
                    }
                    // generate the maximum protected size
                    $asset_type_id = AssetType::where('asset_type_name', '=', 'protected')->pluck('id');
                    $img = $this->imageManager->make($asset->asset_file_uri);
                    $img->resize($this->protected['width'], $this->protected['height'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $img->encode('jpg');
                    $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'/'.$asset->asset_sequence.'_protected.jpg';
                    $this->filesystem->put($imgPath, $img);
                    $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                }
            }
        }
    }

    public function createAsset($imgPath, $asset_type_id, $object_id, $sequence, $source_id) {
        $asset = Asset::firstOrNew(['object_id' => $object_id, 'asset_type_id' => $asset_type_id, 'source_id' => $source_id]);
        $asset->asset_file_uri = $imgPath;
        $asset->asset_type_id = $asset_type_id;
        $asset->object_id = $object_id;
        $asset->asset_sequence = $sequence;
        $asset->source_id = $source_id;
        $asset->save();
    }

    public function deleteAssetRecords($object) {
        // create list of asset_ids to delete
        $asset_ids = [];
        foreach ($object->assets as $asset) {
            // never delete the source
            if($asset->type->asset_type_name != 'source') {
                // if object has proper rights
                if ($object->can_zoom == 1 && $object->can_download == 1) {
                    // if it has old protected assest
                    if ($asset->type->asset_type_name == 'protected') {
                        array_push($asset_ids, $asset->id);
                    }
                } else {
                    if ($asset->type->asset_type_name != 'protected') {
                        // if the size is smaller than the protected size
                        foreach ($this->sizes as $key => $value) {
                            if (strtolower($key) == $asset->type->asset_type_name) {
                                if ($value >= $this->protected['width']) {
                                    array_push($asset_ids, $asset->id);
                                }
                            }
                        }
                        // always add 'dzi' and 'jsonp' if they exist
                        if ($asset->type->asset_type_name == 'dzi') array_push($asset_ids, $asset->id);
                        if ($asset->type->asset_type_name == 'jsonp') array_push($asset_ids, $asset->id);
                    }
                }
            }
        }
        Asset::destroy($asset_ids);
    }
}