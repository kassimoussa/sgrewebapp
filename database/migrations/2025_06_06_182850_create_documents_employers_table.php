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
        Schema::create('documents_employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->onDelete('cascade');
            $table->enum('type_document', ['piece_identite', 'justificatif_domicile', 'autre']);
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille_fichier')->nullable(); // en bytes
            $table->string('extension', 10)->nullable();
            $table->timestamps();
            
            $table->index(['employer_id']);
            $table->index(['type_document']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_employers');
    }
};
