<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('proprietes', function (Blueprint $table) {
            $table->string('identifier')->primary(); // Matalan: SP-123456789
            $table->string('name');
            $table->string('siret')->nullable();
            $table->string('city');
            $table->string('country');
            $table->string('address')->nullable();
            // zidi ga3 l'champs li m7tajahom mn l-ERD
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proprietes');
    }
};