<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_chunk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model', 150);
            $table->unsignedSmallInteger('dimensions');
            $table->longText('vector_data'); // JSON float array, L2-normalized
            $table->timestamps();
            $table->unique(['note_chunk_id', 'model']);
            $table->index(['user_id', 'model']);
        });

        Schema::table('ai_settings', function (Blueprint $table) {
            $table->string('embedding_provider', 50)->nullable();
            $table->string('embedding_base_url', 500)->nullable();
            $table->string('embedding_model', 150)->nullable();
            $table->text('embedding_api_key_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', fn (Blueprint $t) => $t->dropColumn(['embedding_provider', 'embedding_base_url', 'embedding_model', 'embedding_api_key_encrypted']));
        Schema::dropIfExists('embeddings');
    }
};
