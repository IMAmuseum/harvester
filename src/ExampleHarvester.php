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
    public function getAllIDs($source = null)
    {
        // do something to get all object ids
        // return $objectIDs;
        $objectIDs = ['results' => ['100', '200', '300'], 'total' => '3'];
        return (object) $objectIDs;
    }

    public function getUpdateIDs($source = null)
    {
        // do something to get all updated object ids
        // return $objectIDs;
        $objectIDs = ['results' => ['1000', '2000', '3000'], 'total' => '3'];
        return (object) $objectIDs;
    }

    public function getObject($uid, $source = null)
    {
        return \Faker\Factory::create();
    }

    public function initialOrUpdateObject($uid, $source = null)
    {
        $faker = $this->getObject($uid, $source);
        // get all data for a specific object $results
        // find object
        $object = Object::firstOrNew(['object_uid' => $uid]);
        // add data to object
        $object->object_title = $faker->catchPhrase;
        // save object
        $object->save();

        // sync - terms, dates, locations, and texts
        $terms = [
            'medium' => $faker->words($nb = 3),
        ];
        $termIDs = $this->createOrFindTerms($terms);
        if ($termIDs) $object->terms()->sync($termIDs);

        $dates = [
            'year' => [
                'date' => $faker->year($max = 'now'),
                'date_at' => $faker->dateTime()
            ]
        ];
        $dateIDs = $this->createOrFindDates($dates);
        if ($dateIDs) $object->dates()->sync($dateIDs);

        $locations = [
                'building' => [
                     'location' => $faker->streetName,
                     'latitude' => $faker->latitude,
                     'longitude' => $faker->longitude
                ]
            ];
        $locationIDs = $this->createOrFindLocations($locations);
        $object->locations()->sync($locationIDs);

        $texts = [
            'attribution' => $faker->paragraph($nbSentences = 3)
        ];
        $this->createOrUpdateTexts($object->id, $texts);
        // push images onto queue for processing
    }
}
