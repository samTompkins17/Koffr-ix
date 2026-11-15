<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->uuid('structure_id')->index();
            $table->string('statut')->default('ouverte'); // ouverte, cloturee
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes');
    }
};
