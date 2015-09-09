<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Commands\HarvestImages;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Contracts\HarvesterInterface;

class HarvestObjectCommand extends Command
{
    use \Illuminate\Foundation\Bus\DispatchesCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:object
                            {--id= : The Laravel database id of the object.}
                            {--accession= : The accession number of the object.}
                            {--imagesOnly=false : Set to true if you only want to update images.}
                            {--source=null}';

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
        // if id is set find object
        if ($this->option('id')) {
            $id =  $this->option('id');
            $object = Object::findOrFail($id);
        }

        // if accession is set find object
        if ($this->option('accession')) {
            $accession_num =  $this->option('object');
            $object = Object::where('accession_num', '=', $accession_num)->firstOrFail();
        }

        // if data is set to true harvest data
        if ($this->option('imagesOnly') != 'true') {
            $source = $this->option('source');
            $this->harvester->initialOrUpdateObject($object->object_uid, 'sync', $source);
        }

        // if images is set to true harvest images
        if ($this->option('imagesOnly') != 'false') {
            $this->info('images');
            config(['queue.default' => 'sync']);
            // Queue command to process images
            $command = new HarvestImages($object->id);
            $this->dispatch($command);
        }
    }
}