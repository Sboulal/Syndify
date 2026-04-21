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
            $table->foreign('propriete_id')->references('id')->on('proprietes')->onDelete('cascade');
            
            $table->string('type'); 
            $table->string('numero_porte');
            $table->string('batiment')->nullable();
            $table->string('etage')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
};