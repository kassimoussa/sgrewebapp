<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('prenom');
            $table->string('nom');
            $table->enum('genre', ['Homme', 'Femme']);
            $table->enum('etat_civil', ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)']);
            $table->date('date_naissance');
            $table->foreignId('nationality_id')->constrained('nationalities');
            $table->date('date_arrivee');
            $table->string('region');
            $table->string('ville');
            $table->string('quartier');
            $table->text('adresse_complete');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['nationality_id']);
            $table->index(['region']);
            $table->index(['date_naissance']);
            $table->index(['is_active']);
            $table->index(['nom', 'prenom']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
