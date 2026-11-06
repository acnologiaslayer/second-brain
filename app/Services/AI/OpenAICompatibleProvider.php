<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Works with OpenAI, LM Studio, OpenRouter, Groq and any /v1/chat/completions endpoint. */
class OpenAICompatibleProvider implements AIProviderInterface
{
    public function __construct(
        protected string $baseUrl,
        protected string $model,
        protected ?string $apiKey = null,
        protected int $timeout = 60,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function http(): PendingRequest
    {
        $req = Http::timeout($this->timeout)->withoutRedirecting()->acceptJson();
        return $this->apiKey ? $req->withToken($this->apiKey) : $req;
    }

    public function testConnection(): ProviderHealthResult
    {
        try {
            $res = $this->http()->get($this->baseUrl.'/models');
        } catch (ConnectionException $e) {
            return new ProviderHealthResult(false, 'Could not reach endpoint: '.$this->baseUrl);
        }
        if ($res->status() === 401 || $res->status() === 403) {
            return new ProviderHealthResult(false, 'Authentication failed. Check your API key.');
        }
        if (! $res->successful()) {
            return new ProviderHealthResult(false, 'Endpoint responded with HTTP '.$res->status());
        }
        $models = collect($res->json('data', []))->pluck('id')->all();
        return new ProviderHealthResult(true, 'Connected. '.count($models).' model(s) available.', $models);
    }

    public function generateResponse(array $messages, array $options = []): AIResponse
    {
        try {
            $res = $this->http()->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.3,
                'stream' => false,
            ] + (! empty($options['json']) ? ['response_format' => ['type' => 'json_object']] : []));
        } catch (ConnectionException $e) {
            throw new AIProviderException('The AI endpoint is unreachable. Check AI settings.');
        }
        match (true) {
            $res->status() === 401 => throw new AIProviderException('AI provider rejected the API key.'),
            $res->status() === 429 => throw new AIProviderException('AI provider rate limit reached. Try again shortly.'),
            ! $res->successful() => throw new AIProviderException('AI provider error (HTTP '.$res->status().').'),
            default => null,
        };
        $content = $res->json('choices.0.message.content');
        if (! is_string($content)) {
            throw new AIProviderException('AI provider returned an unexpected response.');
        }
        return new AIResponse(trim($content), $res->json('model', $this->model), $res->json('usage', []));
    }
}
