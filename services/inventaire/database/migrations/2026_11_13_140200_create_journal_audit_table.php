<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_audit', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utilisateur_id')->nullable()->index();
            $table->string('action');
            $table->string('objet')->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('date')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_audit');
    }
};
