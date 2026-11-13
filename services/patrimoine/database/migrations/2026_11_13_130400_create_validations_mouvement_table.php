<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Décisions de validation : table en ajout seul. Le mouvement lui-même
        // n'est jamais modifié ; sa décision est la dernière ligne de cette table.
        Schema::create('validations_mouvement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mouvement_id')->unique();
            $table->uuid('decideur_id');
            $table->string('decision'); // validee, refusee
            $table->text('motif')->nullable();
            $table->timestamp('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations_mouvement');
    }
};
