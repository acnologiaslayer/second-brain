<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function testConnection(): ProviderHealthResult;

    /** @param array<int, array{role: string, content: string}> $messages */
    public function generateResponse(array $messages, array $options = []): AIResponse;
}
