<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\Knowledge\NoteIndexingService;
use Illuminate\Http\Request;

/** Quick Capture page (spec 12): fast entry, inbox list, convert-to-note action. */
class CaptureController extends Controller
{
    public function index(Request $request)
    {
        return view('capture.index', [
            'inbox' => $request->user()->notes()->where('note_type', 'inbox')->latest()->get(),
        ]);
    }

    public function store(Request $request, NoteIndexingService $indexer)
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:20000']]);
        $title = str($data['content'])->trim()->before("\n")->limit(80)->toString();
        $note = $request->user()->notes()->create(['title' => $title ?: 'Quick capture', 'content' => $data['content'], 'note_type' => 'inbox']);
        $indexer->index($note);
        return back()->with('status', 'Captured to inbox.');
    }

    /** Convert an inbox item into a regular note by opening it in the editor as a proper type. */
    public function convert(Note $note)
    {
        $this->authorize('update', $note);
        abort_unless($note->note_type === 'inbox', 422);
        $note->update(['note_type' => 'text']);
        return redirect()->route('notes.edit', $note)->with('status', 'Moved out of the inbox. Add a title, category and tags.');
    }
}
