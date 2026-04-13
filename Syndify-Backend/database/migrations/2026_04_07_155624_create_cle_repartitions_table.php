<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('unit_to_key');
        Schema::dropIfExists('cle_repartitions');
        Schema::enableForeignKeyConstraints();

        // عاد كنكرييو الطابلو من جديد
        Schema::create('cle_repartitions', function (Blueprint $table) {
            $table->id();
            $table->string('propriete_id'); 
            $table->string('nom'); 
            $table->decimal('tantiemes_total', 10, 2)->default(0); 
            $table->text('notes')->nullable(); 
            $table->timestamps();

            $table->foreign('propriete_id')
                  ->references('identifier')->on('proprietes')
                  ->onDelete('cascade'); 
        });
    }
    public function down()
    {
        Schema::dropIfExists('cle_repartitions');
    }
};