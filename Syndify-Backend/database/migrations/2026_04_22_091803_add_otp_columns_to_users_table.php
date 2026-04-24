<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Kan-t2kdou wach l-colonne makaynach 3ad kan-zidouha bach may-w9e3ch mouchkil
            if (!Schema::hasColumn('users', 'identifier')) {
                $table->string('identifier')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'activation_code')) {
                $table->string('activation_code')->nullable();
            }
            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'agreed_on_terms')) {
                $table->boolean('agreed_on_terms')->default(false);
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('En attente d’activation');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'identifier', 
                'phone', 
                'activation_code', 
                'otp_expires_at', 
                'agreed_on_terms', 
                'status'
            ]);
        });
    }
};