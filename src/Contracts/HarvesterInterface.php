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

    // Part of the HavesterAbstract
    public function createTypes();
    public function createOrFindFields($model, $fields);
    public function createOrUpdateTexts($object_id, $texts);
    public function createOrUpdateAssets($asset_type_id, $object_id, $images);
    public function createOrUpdateActors($actors);
}
