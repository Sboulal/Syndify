<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_as_owner', function (Blueprint $table) {
            // Kan-zidou l-colonne 'solde' b type Decimal (flouss) w l-valeur par défaut hiya 0
            $table->decimal('solde', 12, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('user_as_owner', function (Blueprint $table) {
            $table->dropColumn('solde');
        });
    }
};