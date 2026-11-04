<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Native Ollama API (/api/chat, /api/tags). */
class OllamaProvider implements AIProviderInterface
{
    public function __construct(
        protected string $baseUrl,
        protected string $model,
        protected int $timeout = 120,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function testConnection(): ProviderHealthResult
    {
        try {
            $res = Http::timeout(10)->get($this->baseUrl.'/api/tags');
        } catch (ConnectionException $e) {
            return new ProviderHealthResult(false, 'Could not reach Ollama at '.$this->baseUrl.'. Is `ollama serve` running?');
        }
        if (! $res->successful()) {
            return new ProviderHealthResult(false, 'Ollama responded with HTTP '.$res->status());
        }
        $models = collect($res->json('models', []))->pluck('name')->all();
        $msg = 'Connected. '.count($models).' model(s) installed.';
        if ($this->model && ! in_array($this->model, $models, true)) {
            $msg .= " Warning: '{$this->model}' is not installed (ollama pull {$this->model}).";
        }
        return new ProviderHealthResult(true, $msg, $models);
    }

    public function generateResponse(array $messages, array $options = []): AIResponse
    {
        try {
            $res = Http::timeout($this->timeout)->post($this->baseUrl.'/api/chat', [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'options' => ['temperature' => $options['temperature'] ?? 0.3],
            ]);
        } catch (ConnectionException $e) {
            throw new AIProviderException('Ollama is unreachable. Start it or update AI settings.');
        }
        if (! $res->successful()) {
            throw new AIProviderException('Ollama error: '.($res->json('error') ?? 'HTTP '.$res->status()));
        }
        return new AIResponse(trim((string) $res->json('message.content')), $this->model, [
            'prompt_tokens' => $res->json('prompt_eval_count'),
            'completion_tokens' => $res->json('eval_count'),
        ]);
    }
}
