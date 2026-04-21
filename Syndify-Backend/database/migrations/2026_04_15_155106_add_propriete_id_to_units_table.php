<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
    {
        // 🟢 Kants2kdou wach l-colonne dëja kayna wla la 9bel manzidouha
        if (!Schema::hasColumn('units', 'propriete_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('propriete_id')->nullable(); // Wla type lli knti dayra
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            //
        });
    }
};
