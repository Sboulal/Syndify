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
    Schema::create('charges_previsionnelles', function (Blueprint $table) {
        $table->string('scp_identifier')->primary();
        $table->string('se_identifier'); // FK dyal Exercice
        
        $table->double('budget', 15, 4)->default(0);
        $table->double('total_encaissements', 15, 4)->default(0);
        $table->double('total_depenses', 15, 4)->default(0);
        
        $table->timestamps();

        $table->foreign('se_identifier')->references('se_identifier')->on('exercices')->onDelete('cascade');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges_previsionnelles');
    }
};
