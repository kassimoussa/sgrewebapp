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
        Schema::create('documents_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('type_document', ['piece_identite', 'photo', 'certificat_medical', 'autre']);
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille_fichier')->nullable(); // en bytes
            $table->string('extension', 10)->nullable();
            $table->timestamps();
            
            $table->index(['employee_id']);
            $table->index(['type_document']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_employees');
    }
};
