<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Community;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualizar comunidades existentes que não têm capa
        $communities = Community::whereNull('cover_url')->get();
        
        foreach ($communities as $community) {
            // Usar placeholder genérico (sistema atual usa iniciais como fallback)
            $community->update(['cover_url' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para null (não há nada específico para reverter)
        // As comunidades voltarão ao estado original
    }
};