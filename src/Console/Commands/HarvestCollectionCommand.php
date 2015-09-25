<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Contracts\HarvesterInterface;
use Imamuseum\Harvester\Models\Object;


class HarvestCollectionCommand extends Command
{

    use \Imamuseum\Harvester\Traits\TimerTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:collection
                            {--initial : Run the inital collection sync.}
                            {--refresh : Run sync all objects to update new data.}
                            {--update : Run the update collection sync.}
                            {--source=null : Option for multi source data sync.}
                            {--only=null : Options images or data}';

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

        if ($this->option('initial')) {
            // do not log inital sync
            config(['harvester.api.log' => false]);
        }

        if ($this->option('initial')) $this->info('Getting all object IDs for seeding.');
        if ($this->option('refresh')) $this->info('Getting all object IDs. for refresh.');
        if ($this->option('update')) $this->info('Getting all updated object IDs.');
        // begin timer
        $begin = microtime(true);

        // create extended types maybe should of a harvester config
        if ($this->option('initial')) $this->harvester->createTypes();

        // get all object_uid from piction
        if ($this->option('initial')) $response = $this->harvester->initialIDs($source);
        if ($this->option('refresh')) {
            $objects = \DB::table('objects')->lists('object_uid');
            $response = [
                'results' => $objects,
                'total' => count($objects),
            ];
            $response = (object) $response;
        }
        if ($this->option('update')) $response = $this->harvester->updateIDs($source);
        $objectIDs = $response->results;

        if (count($objectIDs) > 0) {
            // start progress display in console
            $this->output->progressStart($response->total);

            foreach ($objectIDs as $objectID) {
                // run the intial update on object to populate all fields
                $this->harvester->initialOrUpdateObject($objectID, config('queue.default'), $only, $source);

                // errors will be logged to console
                if (isset($object['error'])) {
                    $this->error($object['error']);
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
    }
}
