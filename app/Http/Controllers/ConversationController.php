<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $latest = $request->user()->conversations()->latest('updated_at')->first();
        return $latest ? redirect()->route('brain.show', $latest) : view('brain.show', ['conversation' => null, 'conversations' => collect()]);
    }

    public function store(Request $request)
    {
        $c = $request->user()->conversations()->create(['title' => 'New conversation']);
        return redirect()->route('brain.show', $c);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        return view('brain.show', [
            'conversation' => $conversation->load('messages'),
            'conversations' => $request->user()->conversations()->latest('updated_at')->get(),
        ]);
    }

    public function destroy(Conversation $conversation)
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();
        return redirect()->route('brain.index');
    }
}
