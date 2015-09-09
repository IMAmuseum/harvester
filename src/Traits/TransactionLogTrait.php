<?php

namespace Imamuseum\Harvester\Traits;

use Imamuseum\Harvester\EloquentTransactionLog as Transaction;

trait TransactionLogTrait
{
    public static function bootTransactionLogTrait()
    {
        $table = (new self)->getTable();

        if (config('harvester.api.log')) {
            static::created(function($item) use ($table) {
                Transaction::log($table, 'created', $item->id);
            });

            static::updated(function($item) use ($table) {
                Transaction::log($table, 'updated', $item->id);
            });

            static::deleted(function($item) use ($table) {
                Transaction::log($table, 'deleted', $item->id);
            });
        }
    }
}
