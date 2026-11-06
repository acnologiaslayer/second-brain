<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Native Ollama /api/embed (e.g. nomic-embed-text, all-minilm). */
class OllamaEmbeddings implements EmbeddingProviderInterface
{
    public function __construct(protected string $baseUrl, protected string $model) {}

    public function model(): string { return $this->model; }

    public function embed(array $texts): array
    {
        if ($texts === []) return [];
        try {
            $res = Http::timeout(120)->withoutRedirecting()->post(rtrim($this->baseUrl, '/').'/api/embed', ['model' => $this->model, 'input' => array_values($texts)]);
        } catch (ConnectionException) {
            throw new AIProviderException('Ollama unreachable for embeddings.');
        }
        if (! $res->successful()) throw new AIProviderException('Ollama embedding error: '.($res->json('error') ?? $res->status()));
        return $res->json('embeddings', []);
    }
}
