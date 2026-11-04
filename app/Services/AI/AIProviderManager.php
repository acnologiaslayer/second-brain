<?php

namespace App\Services\AI;

use App\Models\AISetting;
use App\Models\User;

class AIProviderManager
{
    public function settingsFor(User $user): AISetting
    {
        return $user->aiSetting ?? new AISetting([
            'provider' => config('services.ai.provider', 'ollama'),
            'base_url' => config('services.ai.base_url'),
            'model' => config('services.ai.model'),
            'api_key_encrypted' => config('services.ai.api_key'),
        ]);
    }

    public function forUser(User $user): AIProviderInterface
    {
        return $this->make($this->settingsFor($user));
    }

    public function make(AISetting $s): AIProviderInterface
    {
        $baseUrl = $s->base_url ?: (AISetting::PROVIDERS[$s->provider]['base_url'] ?? null);
        if (! $baseUrl || ! $s->model) {
            throw new AIProviderException('AI is not configured yet. Set an endpoint and model in AI Settings.');
        }

        return match ($s->provider) {
            'ollama' => new OllamaProvider($baseUrl, $s->model),
            'lm_studio', 'openai_compatible' => new OpenAICompatibleProvider($baseUrl, $s->model, $s->api_key_encrypted),
            default => throw new AIProviderException("Unknown provider [{$s->provider}]."),
        };
    }
}
