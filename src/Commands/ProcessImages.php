<?php

namespace Imamuseum\Harvester\Commands;

use Illuminate\Console\Command;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Deepzoom\ImageCreator;
use Deepzoom\StreamWrapper\File;
use Deepzoom\Descriptor;
use Deepzoom\ImageAdapter\GdThumb;
use DB;

class ProcessImages extends Command implements SelfHandling, ShouldBeQueued
{
    use InteractsWithQueue;

    public $objectId;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($objectId)
    {
        $this->object_id = $objectId;
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $obj_id = $this->object_id;

        // Get all piction urls for this object
        $piction_urls = DB::table('objects')
            ->join('assets', 'objects.id', '=', 'assets.object_id')
            ->join('asset_types', function ($join) {
                $join->on('assets.asset_type_id', '=', 'asset_types.id')
                    ->where('asset_types.asset_type_name', '=', 'piction');
            })
            ->select('objects.id', 'objects.object_uid', 'assets.asset_file_uri', 'asset_types.asset_type_name', 'assets.source_id')
            ->where('object_uid', '=', $obj_id)
            ->get();

        // Set initial id for images
        $img_id = 0;

        // Build filepath
        $img_path = 'images/' . $obj_id . '/';

        // Delete the old directories files
        if (file_exists(public_path($img_path))) {
            // Remove old image from DB
            //$this->info('Deleting old image assets.');
            foreach($piction_urls as $asset){
                $this->delete($asset->id);
            }

            $this->fileDelete(public_path($img_path));
        }

        // Create object assets filepath if it does not exist
        mkdir(public_path($img_path), 0777, true);

        // Loop through asset urls to process
        foreach($piction_urls as $asset){

            // Create the assets in the filesystem
            $img = $this->create($obj_id, $img_id, $asset->asset_file_uri, $img_path);

            // Store the new assets in DB
            $this->store($asset, $img_id, $img);

            $img_id++;
        }
    }

    /**
     * Create image derivitives and DZI files
     *
     * @param  int $obj_id
     * @param  int $img_id
     * @param  url $piction_url
     * @return Response
     */
    private function create($obj_id, $img_id, $piction_url, $img_path)
    {
        // Store filenames and sizes
        $img = [
            'original'  => $img_path . $img_id . '_original.jpg',
            'thumb'     => $img_path . $img_id . '_thumb.jpg',
            'medium'    => $img_path . $img_id . '_medium.jpg',
            'large'     => $img_path . $img_id . '_large.jpg',
            'dzi'       => $img_path . $img_id . '.dzi',
            'dzi_files' => $img_path . $img_id . '_files'
        ];

        // Copy image from url
        copy($piction_url, public_path($img['original']));

        $sizes = config('piction.sizes');

        // Create derivatives of images
        foreach ($sizes as $k => $v){
            $img[$k] = $img_path . $img_id . '_' . $k . '.jpg';
            //$this->info('Creating ' . $img[$k]);
            $this->makeDerivative(public_path($img['original']), public_path($img[$k]), $v);
        }

        //$this->info('Creating tiles and DZI files.');

        // Create the DZI and tile images
        $deep = new ImageCreator(new File(), new Descriptor(new File()), new GdThumb());
        $deep->create(realpath(public_path($img['original'])), public_path($img_path . $img_id . '.dzi'));

        return $img;
    }

    /**
     * Store a newly created images in database.
     *
     * @param  int $obj_id
     * @param  int $img_id
     * @param  array $img
     * @return Response
     */
    private function store($asset, $img_id, $img)
    {
        //$this->info('Storing new asset assets in DB.');

        // Loop through assets to store them
        foreach ($img as $k => $v){

            // Get the current asset type from the DB
            $type = DB::table('asset_types')
                ->select('id')
                ->where('asset_type_name', '=', $k)
                ->first();

            // If the asset type does not exist, lets create it
            if(is_null($type)) {
                $type_id = DB::table('asset_types')
                    ->insertGetId(['asset_type_name' => $k, 'asset_type_desc' => $k, 'created_at' => date("Y-m-d H:i:s"), 'updated_at' => date("Y-m-d H:i:s")], 'id');
            } else {
                $type_id = $type->id;
            }

            // Insert statement to store asset in the database
            $stored_img = DB::table('assets')->insert([
                            'object_id' => $asset->id,
                            'asset_type_id' => $type_id,
                            'asset_sequence' => $img_id,
                            'asset_file_uri' => $v,
                            'source_id' => $asset->source_id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
        }
    }

    /**
     * Delete old images from the database.
     *
     * @param  int $obj_id
     * @return Response
     */
    private function delete($obj_id)
    {
        // Delete statement to remove images in the database
        DB::table('assets')
            ->join('asset_types', function ($join) {
                $join->on('assets.asset_type_id', '=', 'asset_types.id')
                    ->where('asset_types.asset_type_name', '<>', 'piction');
            })
            ->where('assets.object_id', '=', $obj_id)
            ->delete();
    }

    /**
     * Delete a file or recursively delete a directory
     *
     * @param string $str Path to file or directory
     */
    private function fileDelete($str) {
        if (is_file($str)) {
            return @unlink($str);
        }
        elseif (is_dir($str)) {
            $scan = glob(rtrim($str,'/').'/*');
            foreach($scan as $index=>$path) {
                $this->fileDelete($path);
            }
            return @rmdir($str);
        }
    }

    /**
     * Creates each derivitive image using the data provided
     *
     * @param  url $src
     * @param  url $dest
     * @param  int $output_width
     * @return void
     */
    private function makeDerivative($src, $dest, $output_width)
    {
        // Read the source image
        $source_image = imagecreatefromjpeg($src);
        $width = imagesx($source_image);
        $height = imagesy($source_image);

        // Find the desired height of this image, relative to the desired width
        $output_height = floor($height * ($output_width / $width));

        // Create a new, "virtual" image
        $virtual_image = imagecreatetruecolor($output_width, $output_height);

        // Copy source image at a resized size
        imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $output_width, $output_height, $width, $height);

        // Create the physical image to its destination
        imagejpeg($virtual_image, $dest);
    }
}
