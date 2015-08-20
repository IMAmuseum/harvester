<?php

namespace Imamuseum\Harvester;

/**
 * Interface HarvesterInterface
 * @package Imamuseum\Harvester
 */
class EloquentTransactionLog
{
    public static function log($table, $event, $itemID)
    {
        $log = new \App\Models\Transaction();
        $log->action = $event;
        $log->table_id = $itemID;
        $log->table = $table;
        $log->save();
    }
}
