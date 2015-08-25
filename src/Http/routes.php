<?php

/*
|--------------------------------------------------------------------------
| Harvester Package Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'api/transactions'], function() {

    Route::get(
        'objects',
        ['uses' => '\Imamuseum\Harvester\Http\Controllers\TransactionApiController@object_list']
    );

    Route::get(
        'objects/{id}',
        ['uses' => '\Imamuseum\Harvester\Http\Controllers\TransactionApiController@object_show']
    );

});
