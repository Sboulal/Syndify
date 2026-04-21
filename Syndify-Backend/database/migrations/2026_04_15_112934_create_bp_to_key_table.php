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
    Schema::create('bp_to_key', function (Blueprint $table) {
        $table->id();
        
        $table->string('scp_identifier'); // FK dyal Budget Previsionnel
        $table->unsignedBigInteger('cle_repartition_id'); // FK dyal Clé de répartition (li 9addina l-bare7)
        
        $table->double('budget', 15, 4)->default(0);
        $table->double('depenses', 15, 4)->default(0);
        
        $table->timestamps();

        $table->foreign('scp_identifier')->references('scp_identifier')->on('charges_previsionnelles')->onDelete('cascade');
        $table->foreign('cle_repartition_id')->references('id')->on('cle_repartitions')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bp_to_key');
    }
};
