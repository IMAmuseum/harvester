<?php

namespace Imamuseum\Harvester;

use Imamuseum\Harvester\Contracts\HarvesterInterface;
use Imamuseum\Harvester\Contracts\HarvesterAbstract;
use Imamuseum\Harvester\Models\Object;

/**
 * Class Example Harvester
 * @package Imamuseum\Harvesters
 */
class ExampleHarvester extends HarvesterAbstract implements HarvesterInterface
{
    public function initialIDs()
    {
        // do something to get all object ids
        // return $objectIDs;
        $objectIDs = ['results' => ['100', '200', '300'], 'total' => '3'];
        return (object) $objectIDs;
    }

    public function updateIDs()
    {
        // do something to get all updated object ids
        // return $objectIDs;
        $objectIDs = ['results' => ['1000', '2000', '3000'], 'total' => '3'];
        return (object) $objectIDs;
    }

    public function initialOrUpdateObject($uid)
    {
        $faker = \Faker\Factory::create();
        // get all data for a specific object $results
        // find object
        $object = Object::where('object_uid', '=', $uid)->firstOrFail();
        // add data to object
        $object->object_title = $faker->catchPhrase;
        // save object
        $object->save();
        //
        // sync - terms, dates, locations, and author
        //
        // push images onto queue for processing
    }
}
