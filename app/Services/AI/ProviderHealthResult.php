<?php

namespace App\Services\AI;

final class ProviderHealthResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $models = [],
    ) {}
}
