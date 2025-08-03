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
        Schema::table('documents_employees', function (Blueprint $table) {
            // Modifier l'enum pour ajouter 'passeport' et 'attestation_identite'
            $table->enum('type_document', [
                'piece_identite', 
                'photo', 
                'certificat_medical', 
                'passeport',
                'attestation_identite',
                'autre'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents_employees', function (Blueprint $table) {
            // Revenir à l'enum original
            $table->enum('type_document', [
                'piece_identite', 
                'photo', 
                'certificat_medical', 
                'autre'
            ])->change();
        });
    }
};