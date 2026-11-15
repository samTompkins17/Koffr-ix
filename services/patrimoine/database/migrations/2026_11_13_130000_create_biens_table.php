<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un bien du patrimoine de l'État. Les liens vers les personnes et
        // les structures passent par des UUID : aucune clé étrangère entre bases.
        Schema::create('biens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code_unique')->unique();
            $table->string('designation');
            $table->string('categorie');
            $table->bigInteger('valeur_acquisition')->default(0); // FCFA, entiers
            $table->date('date_acquisition');
            $table->string('etat')->default('bon');       // bon, usage, hors_service
            $table->string('statut')->default('en_service'); // en_service, sorti
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('localisation')->nullable();
            $table->uuid('structure_id')->index();
            $table->uuid('responsable_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
