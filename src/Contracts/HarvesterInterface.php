<?php

namespace Imamuseum\Harvester\Contracts;

/**
 * Interface HarvesterInterface
 * @package Imamuseum\Harvester
 */
interface HarvesterInterface
{
    public function initialIDs($source);
    public function updateIDs($source);
    public function initialOrUpdateObject($uid, $queue, $source);

    // Part of the HavesterAbstract
    public function createTypes();
    public function createOrFindTerms($fields);
    public function createOrFindDates($fields);
    public function createOrFindLocations($fields);
    public function createOrUpdateTexts($object_id, $texts);
    public function createOrUpdateAssetSource($object_id, $images);
    public function createOrUpdateActors($actors);
}
