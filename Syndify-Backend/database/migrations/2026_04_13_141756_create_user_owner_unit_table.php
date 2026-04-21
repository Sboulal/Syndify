<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_owner_unit', function (Blueprint $table) {
            $table->id();
            
            // 🟢 L'ID dyal l'user (Integer)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            
            // 🟢 HADI LI NAYD 3LIHA LFILM (Khass tkon unit_id machi 7aja khra)
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade'); 
            
            $table->integer('status')->default(1); // 1 = Actif, 2 = Inactif
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_owner_unit');
    }
};