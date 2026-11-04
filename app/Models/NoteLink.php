<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteLink extends Model
{
    protected $fillable = ['user_id', 'source_note_id', 'target_note_id', 'label'];

    public function source(): BelongsTo { return $this->belongsTo(Note::class, 'source_note_id'); }
    public function target(): BelongsTo { return $this->belongsTo(Note::class, 'target_note_id'); }
}
