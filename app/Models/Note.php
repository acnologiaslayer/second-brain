<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['text', 'idea', 'resource', 'study', 'inbox'];

    protected $fillable = ['title', 'content', 'note_type', 'category_id', 'is_favorite', 'is_pinned'];

    protected $casts = ['is_favorite' => 'boolean', 'is_pinned' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function tags(): BelongsToMany { return $this->belongsToMany(Tag::class)->withTimestamps(); }
    public function chunks(): HasMany { return $this->hasMany(NoteChunk::class); }
    public function outgoingLinks(): HasMany { return $this->hasMany(NoteLink::class, 'source_note_id'); }
    public function incomingLinks(): HasMany { return $this->hasMany(NoteLink::class, 'target_note_id'); }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%"));
    }
}
