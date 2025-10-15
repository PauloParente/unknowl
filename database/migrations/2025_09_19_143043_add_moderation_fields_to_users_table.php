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
        Schema::table('users', function (Blueprint $table) {
            // Campos para controle de ban global
            $table->boolean('is_banned_globally')->default(false)->after('bio');
            $table->timestamp('banned_at')->nullable()->after('is_banned_globally');
            $table->text('ban_reason')->nullable()->after('banned_at');
            $table->foreignId('banned_by')->nullable()->after('ban_reason');
            $table->timestamp('banned_until')->nullable()->after('banned_by');
            
            // Campos para mute global
            $table->boolean('is_muted_globally')->default(false)->after('banned_until');
            $table->timestamp('muted_at')->nullable()->after('is_muted_globally');
            $table->timestamp('muted_until')->nullable()->after('muted_at');
            
            // Campos para warnings
            $table->integer('warning_count')->default(0)->after('muted_until');
            $table->timestamp('last_warning_at')->nullable()->after('warning_count');
            
            // Constraints
            $table->foreign('banned_by')->references('id')->on('users')->onDelete('set null');
            
            // Índices
            $table->index(['is_banned_globally', 'banned_until']);
            $table->index(['is_muted_globally', 'muted_until']);
            $table->index('warning_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['banned_by']);
            $table->dropIndex(['is_banned_globally', 'banned_until']);
            $table->dropIndex(['is_muted_globally', 'muted_until']);
            $table->dropIndex(['warning_count']);
            
            $table->dropColumn([
                'is_banned_globally',
                'banned_at',
                'ban_reason',
                'banned_by',
                'banned_until',
                'is_muted_globally',
                'muted_at',
                'muted_until',
                'warning_count',
                'last_warning_at',
            ]);
        });
    }
};