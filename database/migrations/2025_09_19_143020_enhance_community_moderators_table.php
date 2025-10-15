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
        // Para SQLite, precisamos recriar a tabela
        Schema::dropIfExists('community_moderators');
        
        Schema::create('community_moderators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'moderator'])->default('moderator');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['community_id', 'user_id']);
            $table->index(['community_id', 'role']);
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_moderators', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn([
                'assigned_by',
                'assigned_at', 
                'permissions',
                'is_active',
                'notes',
                'role'
            ]);
            
            // Restaurar o campo role original
            $table->string('role')->default('moderator');
        });
    }
};