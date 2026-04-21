<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('depense_for_owner', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('depense_id');
            $table->string('user_id'); 
            $table->decimal('amount_due', 10, 2);
            $table->timestamps();

            $table->foreign('depense_id')->references('id')->on('depenses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('depense_for_owner');
    }
};