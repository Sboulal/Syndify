<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Table principale des Appels de fonds
        Schema::create('appels_fonds', function (Blueprint $table) {
            $table->id();
            $table->string('af_identifier')->unique(); // Ex: AF-12345
            $table->string('se_identifier'); // Lien m3a l'exercice
            $table->unsignedBigInteger('cle_repartition_id')->nullable(); // Gha l-exceptionnel li fih hadchi
            
            $table->enum('type_charge', ['previsionnel', 'travaux']);
            $table->enum('sub_type', ['planifie', 'exceptionnel']);
            
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            
            $table->boolean('is_generated')->default(false);
            $table->boolean('is_sent')->default(false);
            
            $table->integer('number_generated')->default(0);
            $table->integer('number_sent')->default(0);
            
            $table->timestamps();
        });

        // 2. Table dyal les documents (Ila makantch 3ndk deja f l-projet)
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // Ex: appel_fonds
                $table->string('file_path');
                $table->timestamps();
            });
        }

        // 3. Table li katjme3 l-propriétaire m3a l'appel de fonds w l-document dyalo
        Schema::create('appf_to_owner', function (Blueprint $table) {
            $table->id();
            $table->string('af_identifier');
            $table->unsignedBigInteger('user_id'); // ID dyal l-propriétaire
            $table->unsignedBigInteger('document_id')->nullable();
            
            $table->decimal('montant_du', 15, 2)->default(0);
            $table->decimal('solde_avant', 15, 2)->default(0); // Kay-t7et ghir mlli kayt-sayfet l-appel
            
            $table->timestamps();

            // L-liaisons (Foreign Keys)
            $table->foreign('af_identifier')->references('af_identifier')->on('appels_fonds')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appf_to_owner');
        Schema::dropIfExists('appels_fonds');
        // Schema::dropIfExists('documents'); // N-khelliwha 7ssn ila kano fiha 7wayej khrin
    }
};