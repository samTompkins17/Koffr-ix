<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Lien local uniquement ; pas de suppression en cascade d'un document.
            $table->foreignUuid('bien_id')->constrained('biens')->restrictOnDelete();
            $table->index('bien_id');
            $table->enum('type', ['facture', 'acte', 'titre', 'photo', 'autre']);
            $table->string('nom_original');
            $table->string('chemin');
            $table->string('mime_type');
            $table->unsignedBigInteger('taille'); // Taille réelle en octets.
            $table->uuid('auteur_id'); // Identité du JWT, sans lien vers auth_db.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
