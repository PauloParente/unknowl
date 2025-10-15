<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'community_id')) {
                return; // safety
            }
            $table->index(['community_id', 'created_at'], 'posts_community_created_idx');
            $table->index(['created_at'], 'posts_created_idx');
            $table->index(['score', 'created_at'], 'posts_score_created_idx');
        });

        Schema::table('community_user', function (Blueprint $table) {
            if (!Schema::hasColumn('community_user', 'user_id')) {
                return; // safety
            }
            $table->index(['user_id'], 'community_user_user_idx');
            $table->index(['community_id'], 'community_user_community_idx');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_community_created_idx');
            $table->dropIndex('posts_created_idx');
            $table->dropIndex('posts_score_created_idx');
        });

        Schema::table('community_user', function (Blueprint $table) {
            $table->dropIndex('community_user_user_idx');
            $table->dropIndex('community_user_community_idx');
        });
    }
};


