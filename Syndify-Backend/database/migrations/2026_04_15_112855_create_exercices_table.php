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
    Schema::create('exercices', function (Blueprint $table) {
        $table->string('se_identifier')->primary(); // L-ID dyal l-exercice
        $table->string('propriete_id'); // FK dyal Propriété
        
        $table->date('start_date');
        $table->date('end_date');
        $table->string('period'); // trimestre, quadrimestre, mensuel
        $table->string('status')->default('en attente'); 
        
        $table->timestamps();

        // La relation m3a la table proprietes
        $table->foreign('propriete_id')->references('id')->on('proprietes')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercices');
    }
};
