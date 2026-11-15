<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Traitement d'un écart : table en ajout seul, un traitement par contrôle.
        Schema::create('traitements_ecart', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('controle_id')->unique();
            $table->uuid('auteur_id');
            $table->string('action'); // regularise, reforme, main_levee, autre
            $table->text('commentaire');
            $table->timestamp('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traitements_ecart');
    }
};
