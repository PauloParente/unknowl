<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_like'); // true = like, false = dislike
            $table->timestamps();

            // Garantir que um usuário só pode votar uma vez por post
            $table->unique(['post_id', 'user_id']);
            
            // Índices para performance
            $table->index(['post_id', 'is_like']);
            $table->index(['user_id', 'is_like']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_votes');
    }
};