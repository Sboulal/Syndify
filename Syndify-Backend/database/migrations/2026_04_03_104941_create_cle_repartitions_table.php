<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cle_repartitions', function (Blueprint $table) {
            $table->id();
            $table->string('propriete_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('propriete_id')->references('identifier')->on('proprietes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cle_repartitions');
    }
};