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
        Schema::create('community_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            
            // Informações da ação
            $table->string('action'); // ModerationAction enum value
            $table->string('target_type')->nullable(); // 'user', 'post', 'comment', 'community'
            $table->unsignedBigInteger('target_id')->nullable(); // ID do target específico
            
            // Detalhes da ação
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable(); // Dados adicionais da ação
            $table->json('previous_data')->nullable(); // Estado anterior (para auditoria)
            
            // Status e resultado
            $table->enum('status', ['pending', 'completed', 'failed', 'reverted'])->default('completed');
            $table->timestamp('expires_at')->nullable(); // Para ações temporárias
            
            $table->timestamps();
            
            // Índices para performance
            $table->index(['community_id', 'created_at']);
            $table->index(['moderator_id', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_moderation_logs');
    }
};