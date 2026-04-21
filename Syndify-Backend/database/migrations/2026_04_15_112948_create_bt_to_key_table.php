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
    Schema::create('bt_to_key', function (Blueprint $table) {
        $table->id();
        
        $table->string('sct_identifier'); // FK dyal Budget Travaux
        $table->unsignedBigInteger('cle_repartition_id'); // FK dyal Clé de répartition
        
        $table->double('budget', 15, 4)->default(0);
        $table->double('depenses', 15, 4)->default(0);
        
        $table->timestamps();

        $table->foreign('sct_identifier')->references('sct_identifier')->on('charges_travaux')->onDelete('cascade');
        $table->foreign('cle_repartition_id')->references('id')->on('cle_repartitions')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bt_to_key');
    }
};
