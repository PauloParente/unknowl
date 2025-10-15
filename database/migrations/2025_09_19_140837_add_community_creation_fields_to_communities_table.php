<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            // Adicionar owner_id como nullable primeiro
            $table->unsignedBigInteger('owner_id')->nullable()->after('id');
            $table->boolean('is_public')->default(true)->after('rules');
            $table->boolean('requires_approval')->default(false)->after('is_public');
            $table->integer('member_count')->default(0)->after('requires_approval');
            $table->timestamp('last_activity_at')->nullable()->after('member_count');
        });

        // Atualizar owner_id para o primeiro usuário disponível para comunidades existentes
        DB::table('communities')
            ->whereNull('owner_id')
            ->update(['owner_id' => DB::table('users')->first()->id ?? 1]);

        // Agora tornar owner_id NOT NULL e adicionar constraint
        Schema::table('communities', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'owner_id',
                'is_public', 
                'requires_approval',
                'member_count',
                'last_activity_at'
            ]);
        });
    }
};
