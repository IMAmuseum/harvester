<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Commands\HarvestImages;
use Imamuseum\Harvester\Models\Object;

class HarvestImagesCommand extends Command
{
    use \Illuminate\Foundation\Bus\DispatchesCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:images
                            {object : The ID of the object}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to process images for an object. Example: $ php artisan collection:images 100';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $id =  $this->argument('object');
        $object = Object::findOrFail($id);

        config(['queue.default' => 'sync']);
        // Queue command to process images
        $command = new HarvestImages($object->id);
        $this->dispatch($command);
    }
}