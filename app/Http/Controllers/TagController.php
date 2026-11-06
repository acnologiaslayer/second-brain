<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index()
    {
        return redirect()->route('categories.index'); // tags are managed on the Organize page
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('tags')->where('user_id', $request->user()->id)],
        ]);
        $request->user()->tags()->create(['name' => mb_strtolower(trim($data['name']))]);
        return back()->with('status', 'Tag created.');
    }

    public function destroy(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);
        $tag->delete(); // pivot rows cascade; notes are kept
        return back()->with('status', 'Tag deleted.');
    }
}
