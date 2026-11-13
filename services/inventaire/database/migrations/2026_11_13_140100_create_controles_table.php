<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Constats de contrôle : table en ajout seul.
        // Un bien ne peut être contrôlé qu'une seule fois par campagne.
        Schema::create('controles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campagne_id');
            $table->uuid('bien_id');
            $table->string('etat_fiche');
            $table->string('etat_constate'); // bon, usage, hors_service, introuvable
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_chemin')->nullable();
            $table->boolean('ecart_detecte')->default(false);
            $table->uuid('agent_id');
            $table->timestamp('date');

            $table->unique(['campagne_id', 'bien_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controles');
    }
};
