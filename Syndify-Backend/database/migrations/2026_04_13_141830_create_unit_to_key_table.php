<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_to_key', function (Blueprint $table) {
            $table->id();
            
            // 🟢 ZIDNA LES COLONNES LI KANO NASSIN
            $table->unsignedBigInteger('cle_repartition_id');
            $table->unsignedBigInteger('unit_id');
            $table->double('tantieme', 15, 4)->default(0);

            // 🟢 ZIDNA LES RELATIONS (Foreign Keys) bach ila msa7ti chi clé, ytms7o 7ta l-lots dyalo mn had l-table
            $table->foreign('cle_repartition_id')->references('id')->on('cle_repartitions')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_to_key');
    }
};