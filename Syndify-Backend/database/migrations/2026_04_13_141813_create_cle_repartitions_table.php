<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cle_repartitions', function (Blueprint $table) {
            $table->id();
            
            // 🟢 HNA TBDLAT: Rje3naha String 7ta hna
            $table->string('propriete_id');
            $table->foreign('propriete_id')->references('id')->on('proprietes')->onDelete('cascade');
            
            $table->string('nom');
            $table->double('tantiemes_total', 15, 4)->default(0); 
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cle_repartitions');
    }
};