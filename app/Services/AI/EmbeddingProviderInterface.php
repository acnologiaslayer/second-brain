<?php

namespace App\Services\AI;

interface EmbeddingProviderInterface
{
    /**
     * @param string[] $texts
     * @return array<int, float[]> one vector per input text, same order
     */
    public function embed(array $texts): array;

    public function model(): string;
}
