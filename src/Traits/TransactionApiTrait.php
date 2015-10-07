<?php

namespace Imamuseum\Harvester\Traits;
use Carbon\Carbon;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Models\Transaction;

trait TransactionApiTrait
{
    public function apiObjectQuery($table, $request) {
        $params = config('harvester.transaction.valid_params');
        // check that all params are valid.
        foreach ($request->all() as $key => $value) {
            if (! in_array($key, $params)) {
                return ['error' => $key . ' is not a valid request parameter.'];
            }
        }

        $take = $request->has('take') ? $request->input('take') : config('harvester.transaction.defaults.take');
        $action = $request->has('action') ? $request->input('action') : null;
        $actions = config('harvester.transaction.valid_actions');
        if (! in_array($action, $actions)) {
            return ['error' => 'action=' . $action . ' is not a valid request parameter.'];
        }

        if ($action != 'deleted') {
            $query = Object::select();
            if (in_array($action, ['updated', 'created', 'modified'])) {
                $query->whereHas('transactions', function ($q) use ($table, $request, $action) {
                        $hours = $request->has('since') ? $request->input('since') : config('harvester.transaction.defaults.since');
                        $since = Carbon::now()->subhours($hours);
                        $q->where('created_at', '>=', $since);
                        // ?action=modified will find both created and update objects
                        if ($action == 'modified') {
                            $q->whereIn('action', ['created', 'updated']);
                        }
                        // ?action=[created,updated]
                        if ($action != 'modified') {
                            $q->where('action', '=', $action);
                        }
                        $q->where('table', '=', $table);
                    })->with(['transactions' => function ($q) use ($table) {
                        $q->where('table', '=', $table);
                }]);
            }
            $query->with(['actors', 'assets', 'assets.type', 'assets.source', 'terms', 'terms.type', 'texts', 'texts.type', 'locations', 'locations.type', 'dates', 'dates.type']);
        }

        if ($action == 'deleted') {
            $query = Transaction::where('table', '=', $table)->where('action', '=', $action)->distinct();
        }
        $request = $query->paginate($take);

        return $request;
    }
}