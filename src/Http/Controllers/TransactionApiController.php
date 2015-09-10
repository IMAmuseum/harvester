<?php

namespace Imamuseum\Harvester\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Transformers\ObjectTransformer;

class TransactionApiController extends Controller
{
    use \Imamuseum\Harvester\Traits\TransactionApiTrait;

    public function __construct(ObjectTransformer $transformer)
    {
        $this->transformer = $transformer;
    }


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function object_list(Request $request)
    {
        $results = $this->apiObjectQuery('objects', $request);

        if (isset($results['error'])) {
            return response()->json($results['error'], 400);
        }

        $results = $this->transformer->collection($results);

        return $results;
        return response()->json($results, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function object_show($id)
    {
        $results = Object::with(['actors', 'assets', 'assets.type', 'assets.source', 'terms', 'terms.type', 'texts', 'texts.type', 'locations', 'locations.type', 'dates', 'dates.type'])->findOrFail($id);

        $results = $this->transformer->item($results);

        return response()->json($results, 200);
    }

}
