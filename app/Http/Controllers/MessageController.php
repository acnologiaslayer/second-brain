<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use App\Services\Knowledge\NoteIndexingService;
use App\Services\Knowledge\PromptBuilder;
use App\Services\Knowledge\RetrievalService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(SendMessageRequest $request, Conversation $conversation, AIProviderManager $ai, RetrievalService $retrieval, PromptBuilder $prompts)
    {
        $this->authorize('update', $conversation);
        $user = $request->user();
        $question = $request->validated('content');

        $conversation->messages()->create(['role' => 'user', 'content' => $question]);
        if ($conversation->title === 'New conversation') {
            $conversation->update(['title' => str($question)->limit(60)->toString()]);
        }

        $sources = $retrieval->retrieve($user, $question);
        $messages = $prompts->messages($prompts->system($sources), $conversation->messages()->get());
        $sourceMeta = $sources->map(fn ($s) => ['note_id' => $s['chunk']->note_id, 'title' => $s['chunk']->note->title, 'score' => $s['score']])->all();

        try {
            $settings = $ai->settingsFor($user);
            $response = $ai->make($settings)->generateResponse($messages);
            $reply = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response->content,
                'metadata' => ['sources' => $sourceMeta, 'model' => $response->model, 'usage' => $response->usage],
            ]);
            $conversation->update(['provider' => $settings->provider, 'model' => $response->model]);
        } catch (AIProviderException $e) {
            $reply = $conversation->messages()->create([
                'role' => 'assistant', 'status' => 'error', 'content' => $e->getMessage(), 'metadata' => ['sources' => $sourceMeta],
            ]);
        }
        $conversation->touch();

        if ($request->expectsJson()) {
            return response()->json(['message' => view('brain._message', ['m' => $reply])->render()]);
        }
        return redirect()->route('brain.show', $conversation);
    }

    /** Save an assistant reply as a note, only after the user reviews and confirms via the note form. */
    public function saveAsNote(Request $request, Message $message)
    {
        $this->authorize('view', $message->conversation);
        abort_unless($message->role === 'assistant' && $message->status !== 'error', 422);

        if ($request->isMethod('get')) {
            return redirect()->route('notes.create', [
                'title' => 'Brain AI: '.str($message->conversation->title)->limit(60),
                'content' => $message->content,
            ]);
        }
        abort(405);
    }
}
