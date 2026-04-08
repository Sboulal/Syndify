<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up()
    {
        Schema::create('user_owner_unit', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // <--- Hna kyna ghi merra we7da
            $table->unsignedBigInteger('unit_id');
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('identifier')->on('users')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('user_owner_unit');
    }
};