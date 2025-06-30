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
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('type_emploi', ['Temps plein', 'Temps partiel', 'Journalier', 'Gardiennage']);
            $table->decimal('salaire_mensuel', 10, 2);
            $table->boolean('est_actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['employer_id']);
            $table->index(['employee_id']);
            $table->index(['est_actif']);
            $table->index(['date_debut']);
            $table->index(['date_fin']);
            
            // Contrainte: un employé ne peut avoir qu'un seul contrat actif
            $table->unique(['employee_id', 'est_actif'], 'unique_active_contract');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
