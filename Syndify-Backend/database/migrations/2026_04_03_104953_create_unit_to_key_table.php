<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('unit_to_key', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('key_id');
            $table->decimal('tantieme', 8, 2)->default(0);
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('key_id')->references('id')->on('cle_repartitions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('unit_to_key');
    }
};