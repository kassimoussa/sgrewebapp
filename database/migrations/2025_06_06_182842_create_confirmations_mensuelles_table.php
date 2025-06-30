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
        Schema::create('confirmations_mensuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')->constrained('contrats')->onDelete('cascade');
            $table->tinyInteger('mois')->unsigned(); // 1-12
            $table->year('annee');
            $table->enum('statut_emploi', ['actif', 'conge', 'absent', 'termine'])->default('actif');
            $table->tinyInteger('jours_travailles')->unsigned()->default(26);
            $table->tinyInteger('jours_absence')->unsigned()->default(0);
            $table->tinyInteger('jours_conge')->unsigned()->default(0);
            $table->decimal('salaire_verse', 10, 2)->nullable();
            $table->text('observations')->nullable();
            $table->timestamp('date_confirmation')->useCurrent();
            $table->timestamps();
            
            $table->index(['contrat_id']);
            $table->index(['annee', 'mois']);
            $table->index(['statut_emploi']);
            
            // Contrainte: une seule confirmation par contrat/mois/année
            $table->unique(['contrat_id', 'mois', 'annee'], 'unique_monthly_confirmation'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmations_mensuelles');
    }
};
