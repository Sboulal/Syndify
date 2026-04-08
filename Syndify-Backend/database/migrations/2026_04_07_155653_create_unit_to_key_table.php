<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unit_to_key', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('unit_id'); // ID ديال الشقة (Lot)
            $table->unsignedBigInteger('key_id'); // ID ديال الـ Clé de répartition
            
            $table->decimal('tantieme', 10, 2)->default(0); // شحال ديال الأسهم عند هاد الشقة فهاد الـ Clé
            
            $table->timestamps();

            // العلاقات (Foreign Keys)
            $table->foreign('unit_id')
                  ->references('id')->on('units') // تأكدي واش الطابلو ديال لي Lots سميتو units عندك
                  ->onDelete('cascade'); 

            $table->foreign('key_id')
                  ->references('id')->on('cle_repartitions')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('unit_to_key');
    }
};