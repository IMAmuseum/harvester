<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Contracts\HarvesterInterface;
use Imamuseum\Harvester\Commands\HarvestImages;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Models\Source;
use Imamuseum\Harvester\Models\Asset;

class HarvestCollectionCommand extends Command
{
    use \Imamuseum\Harvester\Traits\TimerTrait;
    use \Illuminate\Foundation\Bus\DispatchesCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:collection
                            {--initial : Run the inital collection sync.}
                            {--refresh : Run sync all objects to update new data.}
                            {--update : Run the update collection sync.}
                            {--export : Export content to a third-party.}
                            {--only=null : Options data or images.}
                            {--source=null : Option for multi source data sync.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest initial or update collection data.';

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
        $only = $this->option('only');
        $objects = \DB::table('objects')->lists('object_uid');
        $objects = [
            'results' => $objects,
            'total' => count($objects)
        ];
        $objects = (object)$objects;
        $deleted_uids = [];

        if ($this->option('initial')) $this->info('Getting all object IDs for seeding.');
        if ($this->option('refresh')) $this->info('Getting all object IDs for refresh.');
        if ($this->option('update')) $this->info('Getting all updated object IDs.');
        // begin timer
        $begin = microtime(true);

        // create extended types maybe should of a harvester config
        if ($this->option('initial')) $this->harvester->createTypes();

        // get all object_uid from piction
        if ($this->option('initial')) {
            $response = $this->harvester->getAllIDs($source);
        }

        // set response to objects in harvester
        if ($this->option('refresh')) {
            $response = $this->harvester->getAllIDs($source);
            // get object ids that are still part of piciton response
            $intersectResponse = array_intersect($objects->results, $response->results);
            // get items that have been deleted from source
            $deleted_uids = array_diff($objects->results, $response->results);
            // set response to intersectResponse
            $response->results = $intersectResponse;
            $response->total = count($intersectResponse);
        }

        if ($this->option('update')) {
            // get updated ids from piciton
            $response = $this->harvester->getUpdateIDs($source);
            // get all ids from piction
            $allResponse = $this->harvester->getAllIDs($source);
            // get deleted ids not in the all response
            $deleted_uids = array_diff($objects->results, $allResponse->results);
            // get ids from all response not currently in harvester
            $added_uids = array_diff($allResponse->results, $objects->results);
            // get ids that are also not in the updated response
            $diffAdded_uids = array_diff($added_uids, $response->results);
            // merge response with diff added
            $response->results = array_merge($response->results, $diffAdded_uids);
            // set count of response
            $response->total = count($response->results);
        }

        $objectIDs = $response->results;

        if ( count($objectIDs) > 0) {
            // start progress display in console
            $this->output->progressStart($response->total);

            foreach ($objectIDs as $objectID) {

                if ($only == 'null') {
                    // Queue artisan command for data only
                    \Artisan::queue('harvest:object', ['--uid' => $objectID, '--only' => 'data', '--source' => $source]);
                    // Queue command to process images
                    $command = new HarvestImages($objectID);
                    $this->dispatch($command);
                }

                if ($only == 'data') {
                    // Queue artisan command for data only
                    \Artisan::queue('harvest:object', ['--uid' => $objectID, '--only' => 'data', '--source' => $source]);
                }

                if ($only == 'images') {
                    // Queue command to process images
                    $command = new HarvestImages($objectID);
                    $this->dispatch($command);
                }

                // advance progress display in console
                $this->output->progressAdvance();
            }

            // calculate time elapsed for command
            $end = microtime(true);
            // complete progress display in console
            $this->output->progressFinish();
            // dispaly total time in console
            $this->info($this->timer($begin, $end));
        } else {
            $this->info('No objects have been updated.');
        }

        if (! empty($deleted_uids)) {
            foreach ($deleted_uids as $object_uid) {
                $object = Object::where('object_uid', '=', $object_uid)->first();
                $object->delete();
                Source::where('object_id', '=', $object->id)->delete();
                Asset::where('object_id', '=', $object->id)->delete();
            }
        }

        // Queue the export command
        if ($this->option('export')) {
            \Artisan::queue('harvest:export', ['--modified' => true]);
            \Artisan::queue('harvest:export', ['--deleted' => true]);
        }
    }
}
