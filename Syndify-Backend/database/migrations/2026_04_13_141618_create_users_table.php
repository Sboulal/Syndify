<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); 
            $table->string('identifier')->nullable()->unique(); // L'identifiant (SU-...)
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('tel')->nullable(); // Awla smitiha phone
            
            // 🟢 Colonnes dyal l-OTP (BLA password)
            $table->string('activation_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->boolean('agreed_on_terms')->default(false);
            $table->string('status')->default('En attente d’activation');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};