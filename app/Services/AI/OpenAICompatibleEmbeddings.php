<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** POST {base}/embeddings: OpenAI, LM Studio, Ollama (/v1), Jina, Mistral, etc. */
class OpenAICompatibleEmbeddings implements EmbeddingProviderInterface
{
    public function __construct(protected string $baseUrl, protected string $model, protected ?string $apiKey = null) {}

    public function model(): string { return $this->model; }

    public function embed(array $texts): array
    {
        if ($texts === []) return [];
        $req = Http::timeout(60)->acceptJson();
        if ($this->apiKey) $req = $req->withToken($this->apiKey);
        try {
            $res = $req->post(rtrim($this->baseUrl, '/').'/embeddings', ['model' => $this->model, 'input' => array_values($texts)]);
        } catch (ConnectionException) {
            throw new AIProviderException('Embedding endpoint unreachable.');
        }
        if (! $res->successful()) throw new AIProviderException('Embedding error (HTTP '.$res->status().').');
        return collect($res->json('data', []))->sortBy('index')->pluck('embedding')->values()->all();
    }
}
