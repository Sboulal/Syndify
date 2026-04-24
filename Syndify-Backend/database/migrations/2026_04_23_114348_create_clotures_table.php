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
    Schema::create('clotures', function (Blueprint $table) {
        $table->id();
        $table->string('se_identifier')->unique(); // L-id dyal l-exercice
        $table->integer('status')->default(0); // 0 = Brouillon, 1 = Finalisé

        // Choix Prévisionnel
        $table->integer('reste_choice_prev')->nullable();
        $table->integer('du_choice_prev')->nullable();
        $table->boolean('send_reminders_prev')->default(false);

        // Choix Travaux
        $table->integer('reste_choice_trav')->nullable();
        $table->integer('du_choice_trav')->nullable();
        $table->boolean('send_reminders_trav')->default(false);

        $table->string('report_link')->nullable(); // L-lien dyal l-PDF
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clotures');
    }
};
