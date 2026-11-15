<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique des mouvements : table en ajout seul.
        Schema::create('mouvements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->string('type'); // affectation, transfert, maintenance, sortie
            $table->timestamp('date');
            $table->uuid('structure_origine_id')->nullable();
            $table->uuid('structure_destination_id')->nullable();
            $table->text('motif');
            $table->string('statut_validation')->default('valide');
            $table->uuid('auteur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements');
    }
};
