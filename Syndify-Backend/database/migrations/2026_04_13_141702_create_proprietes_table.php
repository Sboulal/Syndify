<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('proprietes', function (Blueprint $table) {
          
            $table->string('id')->primary(); 
            $table->string('nom')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proprietes');
    }
};