<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComptesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id'); // ID de l'utilisateur associé au compte
            $table->decimal('solde', 15, 2)->default(0); // Solde du compte
            $table->decimal('plafond_solde', 15, 2); // Plafond de solde
            $table->decimal('cumul_transaction_mensuelle', 15, 2)->default(0); // Cumul des transactions mensuelles
            $table->timestamps();

            // Clé étrangère vers la table utilisateurs
            $table->foreign('utilisateur_id')->references('id')->on('utilisateurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comptes');
    }
}