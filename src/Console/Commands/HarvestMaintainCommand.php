<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Contracts\HarvesterInterface;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Models\Source;
use Imamuseum\Harvester\Models\Asset;
use Imamuseum\Harvester\Commands\HarvestImages;

class HarvestMaintainCommand extends Command
{
    use \Illuminate\Foundation\Bus\DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:maintain
                            {--source=null : Option for multi source data sync.}
                            {--export : Export content to a third-party.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maintenance command.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(HarvesterInterface $harvester)
    {
        $this->harvester = $harvester;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $source = $this->option('source');
        // get all object ids from source
        $source_object_uids = $this->harvester->getAllIDs($source);
        // get all harvester object uids
        $objects = \DB::table('objects')->lists('object_uid');
        $harvester_object_uids = [
            'results' => $objects,
            'total' => count($objects)
        ];
        $harvester_object_uids = (object)$harvester_object_uids;

        // get harvester_object_uids not in the source_object_uids array
        $harvester_delete = array_diff($harvester_object_uids->results, $source_object_uids->results);
        // delete harvesterUIDs if not in sourceIDs
        foreach ($harvester_delete as $object_uid) {
            $object = Object::where('object_uid', '=', $object_uid)->first();
            $object->delete();
            Source::where('object_id', '=', $object->id)->delete();
            Asset::where('object_id', '=', $object->id)->delete();
        }

        // get source_object_uids not in the harvester_object_uids array
        $harvester_queue = array_diff($source_object_uids->results, $harvester_object_uids->results);
        // queue sourceIDs if not in harvesterIDs
        foreach ($harvester_queue as $object_uid) {
            // Queue artisan command for data only
            \Artisan::queue('harvest:object', ['--uid' => $object_uid, '--only' => 'data', '--source' => $source]);
            // Queue command to process images
            $command = new HarvestImages($object_uid);
            $this->dispatch($command);
        }

        // compare sourece assets to harvester assets
        foreach ($source_object_uids->results as $source_uid) {
            // if source uid has not already been queued then compare assets
            if (! in_array($source_uid, $harvester_queue)) {
                // get source object
                $source_object = $this->harvester->getObject($source_uid, $source);
                // get source object assets
                $source_asset_ids = [];
                foreach ($source_object->images as $asset) {
                    $source_asset_ids[] = $asset->source_id;
                }
                // get harvester object
                $harvester_object = Object::with(['source'])->where('object_uid', '=', $source_uid)->first();

                // get harvester object
                $harvester_asset_ids = [];
                foreach ($harvester_object->source as $asset) {
                    $harvester_asset_ids[] = $asset->origin_id;
                }

                // get harvester_asset_ids not in the source_asset_ids array
                $harvester_asset_delete = array_diff($harvester_asset_ids, $source_asset_ids);
                // delete harvester source assets no longer found in the source
                $harvester_delete_queue = false;
                foreach($harvester_asset_delete as $origin_id) {
                    // remove source reference from database
                    Source::where('object_id', '=', $harvester_object->id)->delete();
                    Asset::where('object_id', '=', $harvester_object->id)->delete();
                    $harvester_delete_queue = true;
                }

                // regenerate images associated with object
                if ($harvester_delete_queue) {
                    // Queue artisan command for data only
                    \Artisan::queue('harvest:object', ['--uid' => $source_uid, '--only' => 'data', '--source' => $source]);
                    // Queue command to process images
                    $command = new HarvestImages($source_uid);
                    $this->dispatch($command);
                }

                // get source_asset_ids not in the harvester_object_ids array
                $harvester_asset_queue = array_diff($source_asset_ids, $harvester_asset_ids);
                if (! empty($harvester_asset_queue) ) {
                    // Queue artisan command for data only
                    \Artisan::queue('harvest:object', ['--uid' => $source_uid, '--only' => 'data', '--source' => $source]);
                    // Queue command to process images
                    $command = new HarvestImages($source_uid);
                    $this->dispatch($command);
                }
            }
        }

        // Queue the export command
        if ($this->option('export')) {
            \Artisan::queue('harvest:export', ['--deleted' => true]);
            \Artisan::queue('harvest:export', ['--modified' => true]);
        }
    }
}
