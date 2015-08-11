<?php

namespace Imamuseum\Harvester\Contracts;

/**
 * Interface HarvesterInterface
 * @package Imamuseum\Harvester
 */
interface HarvesterInterface
{
    public function initialIDs();
    public function updateIDs();
    public function initialOrUpdateObject($uid);

    // Part of the HavesterTrait
    public function createOrUpdateObject($objectID);
    public function createTypes();
    public function createOrFindFields($model, $fields);
    public function createOrUpdateTexts($objectID, $texts);
    public function createAssets($objectID, $images);
    public function createOrUpdateActors($actors);
}
