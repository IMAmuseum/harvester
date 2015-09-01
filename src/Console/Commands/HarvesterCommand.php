<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Contracts\HarvesterInterface;


class HarvesterCommand extends Command
{

    use \Imamuseum\Harvester\Traits\TimerTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:harvest {--initial} {--update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        if ($this->option('initial')) {
            // do not log inital sync
            config(['harvester.log' => false]);
        }

        if ($this->option('initial')) $this->info('Getting all object IDs for seeding.');
        if ($this->option('update')) $this->info('Getting all updated object IDs.');
        // begin timer
        $begin = microtime(true);

        // create extended types maybe should of a harvester config
        if ($this->option('initial')) $this->harvester->createTypes();

        // get all object_uid from piction
        if ($this->option('initial')) $response = $this->harvester->initialIDs();
        if ($this->option('update')) $response = $this->harvester->updateIDs();
        $objectIDs = $response->results;

        // start progress display in console
        $this->output->progressStart($response->total);

        foreach ($objectIDs as $objectID) {
            // run the intial update on object to populate all fields
            $this->harvester->initialOrUpdateObject($objectID);

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
    }
}
