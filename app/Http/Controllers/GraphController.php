<?php

namespace App\Http\Controllers;

use App\Services\Knowledge\RelatedNotesService;
use Illuminate\Http\Request;

class GraphController extends Controller
{
    public function index(Request $request, RelatedNotesService $related)
    {
        $graph = $related->graph($request->user());
        return view('graph.index', [
            'graph' => $graph,
            'counts' => collect($graph['edges'])->countBy('kind')->all(),
        ]);
    }
}
