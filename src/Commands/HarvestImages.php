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
        $this->sizes = config('harvester.sizes');
        $this->deepzoom = DeepzoomFactory::create([
            'path' => public_path('images/'.$object_id),
            'driver' => 'imagick'
        ]);
        $this->filesystem = new Filesystem(new Local(public_path('images')));
        $this->imageManager = new ImageManager(['driver' => 'imagick']);
        $this->update = false;
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        // get object
        $object = Object::with('assets', 'assets.type')->findOrFail($this->object_id);

        // delete directory if it exists
        if ($this->filesystem->has($object->id)) {
            $this->filesystem->deleteDir($object->id);
            // mark as update to handle possible asset delete
            $this->update = true;
        }

        foreach ($object->assets as $asset) {
            if($asset->type->asset_type_name == 'source') {
                // if object has proper rights
                if ($object->can_zoom == 1 && $object->can_download == 1) {

                    if ($this->update) {
                        // get restrict id and delete assets if rights permission has changed
                        $restrict_type_id = AssetType::where('asset_type_name', '=', 'restrict')->pluck('id');
                        Asset::where('asset_type_id', $restrict_type_id)->where('source_id', '=', $asset->source_id)->delete();
                    }

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
                        $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'_'.$key.'.jpg';
                        $this->filesystem->put($imgPath, $img);
                        $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                    }
                // generate the sizes below the restricted width
                } else {
                    // setup array of type ids to be deleted if this is an update
                    if ($this->update) {
                        $type_ids = [];
                        $type_id = AssetType::where('asset_type_name', '=', 'dzi')->pluck('id');
                        if (! in_array($type_id, $type_ids, true)) {
                            array_push($type_ids, $type_id);
                        }
                        $type_id = AssetType::where('asset_type_name', '=', 'jsonp')->pluck('id');
                        if (! in_array($type_id, $type_ids, true)) {
                            array_push($type_ids, $type_id);
                        }
                    }
                    foreach ($this->sizes as $key => $value) {

                        if ($this->update && $value >= config('harvester.restrict.width')) {
                            // get existing ids and delete assets if rights permission change
                            $type_id = AssetType::where('asset_type_name', '=', $key)->pluck('id');
                            if (! in_array($type_id, $type_ids, true)) {
                                array_push($type_ids, $type_id);
                            }
                        }

                        if ($value <= config('harvester.restrict.width')) {
                            $asset_type_id = AssetType::where('asset_type_name', '=', $key)->pluck('id');
                            $img = $this->imageManager->make($asset->asset_file_uri);
                            // prevent possible upsizing
                            $img->resize($value, null, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
                            $img->encode('jpg');
                            $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'_'.$key.'.jpg';
                            $this->filesystem->put($imgPath, $img);
                            $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);
                        }
                    }
                    // generate the maximum restricted size
                    $asset_type_id = AssetType::where('asset_type_name', '=', 'restrict')->pluck('id');
                    $img = $this->imageManager->make($asset->asset_file_uri);
                    $img->resize(config('harvester.restrict.width'), config('harvester.restrict.height'), function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $img->encode('jpg');
                    $imgPath = 'images/'.$object->id.'/'.$asset->asset_sequence.'_restrict.jpg';
                    $this->filesystem->put($imgPath, $img);
                    $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->asset_sequence, $asset->source_id);

                    if ($this->update) {
                        foreach ($type_ids as $id) {
                            Asset::where('asset_type_id', $id)->where('source_id', '=', $asset->source_id)->delete();
                        }
                    }
                }
            }
        }
    }

    public function createAsset($imgPath, $asset_type_id, $object_id, $sequence, $source_id) {
            $asset = Asset::firstOrNew(['asset_file_uri' => $imgPath]);
            $asset->asset_type_id = $asset_type_id;
            $asset->object_id = $object_id;
            $asset->asset_sequence = $sequence;
            $asset->source_id = $source_id;
            $asset->save();
    }
}