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
        Schema::create('community_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banned_by')->constrained('users')->cascadeOnDelete();
            
            // Detalhes do ban
            $table->text('reason')->nullable();
            $table->enum('type', ['permanent', 'temporary'])->default('permanent');
            $table->timestamp('expires_at')->nullable(); // Para bans temporários
            $table->boolean('is_active')->default(true);
            
            // Dados para auditoria
            $table->json('metadata')->nullable(); // Dados adicionais
            $table->foreignId('unbanned_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('unbanned_at')->nullable();
            $table->text('unban_reason')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['community_id', 'user_id']); // Um ban por comunidade por usuário
            $table->index(['community_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index('banned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_bans');
    }
};