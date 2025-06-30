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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('prenom')->nullable();
            $table->string('nom')->nullable();
            $table->enum('genre', ['Homme', 'Femme'])->nullable();
            $table->string('telephone')->unique();
            $table->string('region')->nullable();
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->string('email')->unique();
            $table->string('mot_de_passe_hash');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['email']);
            $table->index(['telephone']);
            $table->index(['region']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
