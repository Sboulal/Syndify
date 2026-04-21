<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_as_owner', function (Blueprint $table) {
            $table->id();
            // 🟢 user_id hwa Integer (m3a Delete Cascade)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // 🟢 propriete_id khassha tkoun STRING bhal la table proprietes
            $table->string('propriete_id'); 
            $table->foreign('propriete_id')->references('id')->on('proprietes')->onDelete('cascade');
            
            $table->integer('status')->default(1); 
            $table->double('balance_prev', 15, 2)->default(0); 
            $table->double('balance_new', 15, 2)->default(0);  
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_as_owner');
    }
};