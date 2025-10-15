<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            // Índices para busca em comunidades
            $table->index('name');
            $table->index('title');
            $table->index('description');
        });

        Schema::table('posts', function (Blueprint $table) {
            // Índices para busca em posts
            $table->index('title');
            $table->index('text');
            $table->index(['community_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Índices para busca em usuários
            $table->index('name');
            $table->index('email');
        });

        Schema::table('comments', function (Blueprint $table) {
            // Índices para busca em comentários (opcional)
            $table->index('text');
            $table->index(['post_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['title']);
            $table->dropIndex(['description']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['text']);
            $table->dropIndex(['community_id', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['email']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['text']);
            $table->dropIndex(['post_id', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};