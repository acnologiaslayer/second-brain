<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteChunk extends Model
{
    protected $fillable = ['note_id', 'user_id', 'chunk_index', 'content', 'content_hash'];

    public function note(): BelongsTo { return $this->belongsTo(Note::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
