<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up()
    {
        Schema::create('user_as_owner', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Hna mktouba mra wehda safi
            $table->string('propriete_id');
            $table->integer('status')->default(0);
            $table->timestamps();

            // Relations
            $table->foreign('user_id')->references('identifier')->on('users')->onDelete('cascade');
            $table->foreign('propriete_id')->references('identifier')->on('proprietes')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('user_as_owner');
    }
};