<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id(); 
            $table->string('sdep_identifier')->unique();
            $table->string('se_identifier'); 
            $table->unsignedBigInteger('cle_repartition_id'); 
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('type_charges'); 
            $table->string('sub_type_charges'); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('depenses');
    }
};