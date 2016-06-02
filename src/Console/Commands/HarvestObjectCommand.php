<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Commands\HarvestImages;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Contracts\HarvesterInterface;

class HarvestObjectCommand extends Command
{
    use \Illuminate\Foundation\Bus\DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:object
                            {--id= : The Laravel database id of the object.}
                            {--uid= : The unique id of object from source data.}
                            {--only=null : Options data or images.}
                            {--source=null : Option for multi source data sync.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest images and/or data for a specific object.';

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

    public function handle()
    {
        $only = $this->option('only');
        $source = $this->option('source');

        // if id is set find object
        if ($this->option('id')) {
            $id =  $this->option('id');
            $object = Object::findOrFail($id);
            $object_uid = $object->object_uid;
        }

        // if accession is set find object
        if ($this->option('uid')) {
            $object_uid =  $this->option('uid');
        }

        if ($only == 'data' || $only == 'null') {
            $this->harvester->initialOrUpdateObject($object_uid, $source);
        }

        if ($only == 'images'|| $only == 'null') {
            // Queue command to process images
            $command = new HarvestImages($object_uid);
            $this->dispatch($command);
        }
    }
}
