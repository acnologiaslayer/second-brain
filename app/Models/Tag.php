<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function notes(): BelongsToMany { return $this->belongsToMany(Note::class)->withTimestamps(); }
}
