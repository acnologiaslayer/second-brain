<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use App\Services\Knowledge\NoteProposalService;
use App\Services\Knowledge\PromptBuilder;
use App\Services\Knowledge\RetrievalService;

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

        // RAG (spec 8.2): user-scoped retrieval, bounded context, structured source references.
        [$context, $sources] = $prompts->context($retrieval->retrieve($user, $question));
        $messages = $prompts->messages($context, $conversation->messages()->get());
        $sourceMeta = $sources->map(fn ($s) => [
            'note_id' => $s['chunk']->note_id,
            'chunk_id' => $s['chunk']->id,
            'title' => $s['chunk']->note->title,
            'score' => $s['score'],
            'method' => $s['method'],
        ])->all();

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
            return response()->json(['message' => view('brain._message', ['m' => $reply])->render(), 'title' => $conversation->title]);
        }
        return redirect()->route('brain.show', $conversation);
    }

    /**
     * Feed My Brain (spec 9): build a proposed note (title, body, suggested category + tags)
     * and show it in the editable note form. Nothing is saved until the user confirms.
     */
    public function saveAsNote(Message $message, NoteProposalService $proposals)
    {
        $this->authorize('view', $message->conversation);
        abort_unless($message->role === 'assistant' && $message->status !== 'error', 422);

        $proposal = $proposals->propose(request()->user(), $message->content, 'Brain AI: '.$message->conversation->title);
        return redirect()->route('notes.create')->with('proposal', $proposal + ['origin' => 'Brain AI answer']);
    }
}
