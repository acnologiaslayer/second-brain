<?php

namespace App\Services\AI;

final class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly array $usage = [],
    ) {}
}
