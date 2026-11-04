<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('note_type', 40)->default('text');
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index('deleted_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notes ADD FULLTEXT notes_fulltext (title, content)');
        }

        Schema::create('note_tag', function (Blueprint $table) {
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['note_id', 'tag_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('provider', 50)->nullable();
            $table->string('model', 150)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->longText('content');
            $table->string('status', 20)->default('complete');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('note_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->string('content_hash', 64);
            $table->timestamps();
            $table->unique(['note_id', 'chunk_index']);
        });

        Schema::create('note_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('target_note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->timestamps();
            $table->unique(['source_note_id', 'target_note_id']);
        });

        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('ollama');
            $table->string('base_url', 500)->nullable();
            $table->string('model', 150)->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ai_settings', 'note_links', 'note_chunks', 'messages', 'conversations', 'note_tag', 'notes', 'tags', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
