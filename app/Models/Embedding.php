<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Embedding extends Model
{
    protected $fillable = ['note_chunk_id', 'user_id', 'model', 'dimensions', 'vector_data'];

    protected $casts = ['vector_data' => 'array'];

    public function chunk(): BelongsTo { return $this->belongsTo(NoteChunk::class, 'note_chunk_id'); }
}
