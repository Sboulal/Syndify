<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // 1. Identifiant personnalisé kima f l'Cahier des charges (Primary Key)
            $table->string('identifier')->primary(); // Ex: SU-1729084354

            // 2. Les informations de base
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();

            // 3. Code d'activation (String 3adiya bach t-hzz l'Hash dyal 60 7erf bla erreur 500)
            $table->string('activation_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // 4. Les booléens w l'statut
            $table->boolean('agreed_on_terms')->default(false);
            $table->boolean('mailing_subs')->default(false);
            $table->enum('status', ['En attente d’activation', 'Actif', 'Inactif'])->default('En attente d’activation');

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            
            // ⚠️ MODIFICATION MOHIMA: rddinaha string() 7it l'identifier dyal l'user wlla string (SU-...)
            $table->string('user_id')->nullable()->index(); 
            
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};