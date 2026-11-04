<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AISetting extends Model
{
    public const PROVIDERS = [
        'ollama' => ['label' => 'Ollama (local)', 'base_url' => 'http://localhost:11434'],
        'lm_studio' => ['label' => 'LM Studio (local)', 'base_url' => 'http://localhost:1234/v1'],
        'openai_compatible' => ['label' => 'OpenAI-compatible API', 'base_url' => 'https://api.openai.com/v1'],
    ];

    protected $table = 'ai_settings';

    protected $fillable = ['provider', 'base_url', 'model', 'api_key_encrypted', 'settings'];

    protected $hidden = ['api_key_encrypted'];

    protected $casts = ['api_key_encrypted' => 'encrypted', 'settings' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function hasApiKey(): bool { return filled($this->api_key_encrypted); }
}
