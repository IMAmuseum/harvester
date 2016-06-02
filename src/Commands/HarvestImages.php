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
use Imamuseum\Harvester\Models\Source;


class HarvestImages extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    protected $object_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($object_uid)
    {
        $this->object_uid = $object_uid;
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
        // get object
        $object = Object::with('assets', 'assets.type', 'source')
                    ->where('object_uid', '=', $this->object_uid)->firstOrFail();

        $this->deepzoom = DeepzoomFactory::create([
            'path' => public_path('images/'.$object->id),
            'driver' => config('harvester.image.driver')
        ]);

        // delete directory if it exists
        if ($this->filesystem->has('images/'.$object->id)) {
            $this->filesystem->deleteDir('images/'.$object->id);
            $this->deleteAssetRecords($object);
        }

        foreach ($object->source as $asset) {
            // load image into memory for this asset
            $img = $this->imageManager->make($asset->source_uri);

            // if object has proper rights
            if ($object->can_zoom == 1 || $object->can_download == 1) {
                // create deepzoom tiles
                if($object->can_zoom == 1) {
                    // get result back form deepzoom
                    $result = $this->deepzoom->makeTiles($asset->source_uri, 'dzi' . $asset->source_sequence, $asset->source_sequence);
                    foreach ($result['data']['output'] as $key => $value) {
                        $asset_type = AssetType::where('asset_type_name', '=', strtolower($key))->first();
                        if ($asset_type) {
                            $asset_type_id = $asset_type->id;
                            $imgPath = 'images/'.$object->id.'/'.$asset->source_sequence.'/'.basename($value);
                            $this->createAsset($imgPath, $asset_type_id, $object->id, $asset->source_sequence, $asset->id);
                        }
                    }
                }
                foreach ($this->sizes as $type => $width) {
                    $this->generateAsset($img, $object->id, $asset, $type, $width);
                }
                if ($object->can_download == 1) {
                    $this->generateAsset($img, $object->id, $asset, 'original', $img->width(), $img->height());
                }
            }

            // generate the sizes below the protected width
            if ($object->protected_size != null) {

                $protected_size = $this->protected[$object->protected_size];
                $img_width = $img->width();
                $img_height = $img->height();
                $aspect_ratio = $img_width / $img_height;

                foreach ($this->sizes as $type => $width) {
                    if ($width <= $protected_size['width']) {
                        if ($aspect_ratio < 1) {
                            $this->generateAsset($img, $object->id, $asset, $type, $width, $protected_size['height']);
                        } else {
                            $this->generateAsset($img, $object->id, $asset, $type, $width, $protected_size['width']);
                        }
                    }
                }
                // check is aspect ratio is horizontal of vertical
                if ($aspect_ratio < 1) {
                    $this->generateAsset($img, $object->id, $asset, 'protected', $protected_size['width'], $protected_size['height']);
                } else {
                    $this->generateAsset($img, $object->id, $asset, 'protected', $protected_size['height'], $protected_size['width']);
                }
            }
        }
        // touch object updated_at timestamp to record transaction
        $object->touch();
    }

    public function generateAsset($img, $object_id, $asset, $type, $width, $height = NULL, $protected = NULL)
    {
        $asset_type = AssetType::where('asset_type_name', '=', strtolower($type))->first();
        if ($asset_type) {
            $asset_type_id = $asset_type->id;
            $temp = clone $img;
            // prevent possible upsizing
            $temp->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $temp->encode('jpg', 100);
            $imgPath = 'images/'.$object_id.'/'.$asset->source_sequence.'/'.$asset->source_sequence.'_'.$type.'.jpg';
            $this->filesystem->put($imgPath, $temp);
            $this->createAsset($imgPath, $asset_type_id, $object_id, $asset->source_sequence, $asset->id);
            unset($temp);
        }
    }

    public function createAsset($imgPath, $asset_type_id, $object_id, $sequence, $source_id)
    {
        $asset = Asset::firstOrNew(['object_id' => $object_id, 'asset_type_id' => $asset_type_id, 'source_id' => $source_id]);
        $asset->asset_file_uri = $imgPath;
        $asset->asset_sequence = $sequence;
        $asset->save();
    }

    public function deleteAssetRecords($object)
    {
        // create list of asset_ids to delete
        $asset_ids = [];
        foreach ($object->assets as $asset) {
            // if object has proper rights
            if ($object->can_zoom == 1 && $object->can_download == 1) {
                // if it has old protected assest
                if ($asset->type->asset_type_name == 'protected') {
                    array_push($asset_ids, $asset->id);
                }
            } else {
                if ($asset->type->asset_type_name != 'protected') {
                    $protected_size = $this->protected[$object->protected_size];
                    // if the size is smaller than the protected size
                    foreach ($this->sizes as $key => $value) {
                        if (strtolower($key) == $asset->type->asset_type_name) {
                            if ($value > $protected_size['width']) {
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
        Asset::destroy($asset_ids);
    }
}
