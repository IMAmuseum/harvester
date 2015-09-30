<?php

namespace Imamuseum\Harvester\Contracts;

/**
 * Interface HarvesterInterface
 * @package Imamuseum\Harvester
 */
interface HarvesterInterface
{
    public function getAllIDs($source);
    public function getUpdateIDs($source);
    public function getObject($uid, $source);
    public function initialOrUpdateObject($uid, $source);

    // Part of the HavesterAbstract
    public function createTypes();
    public function createOrFindTerms($fields);
    public function createOrFindDates($fields);
    public function createOrFindLocations($fields);
    public function createOrUpdateTexts($object_id, $texts);
    public function createOrUpdateAssetSource($object_id, $images);
    public function createOrUpdateActors($actors);
}
