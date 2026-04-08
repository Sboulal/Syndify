<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('propriete_id');
            $table->string('type'); // Appartement, Garage, etc.
            $table->string('batiment')->nullable();
            $table->string('etage')->nullable();
            $table->string('numero_porte');
            $table->timestamps();

            // L'Foreign Key l-Propriete
            $table->foreign('propriete_id')
                  ->references('identifier')
                  ->on('proprietes')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
};