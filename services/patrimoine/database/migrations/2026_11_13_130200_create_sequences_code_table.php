<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compteur par ministère et par année pour fabriquer PAT-SAN-2026-0412.
        // La ligne est verrouillée (lockForUpdate) pendant l'incrément.
        Schema::create('sequences_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ministere_code');
            $table->integer('annee');
            $table->integer('dernier_numero')->default(0);
            $table->unique(['ministere_code', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences_code');
    }
};
