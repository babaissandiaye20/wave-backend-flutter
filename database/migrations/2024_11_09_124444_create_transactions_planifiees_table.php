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
        Schema::create('transactions_planifiees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compte_id');
            $table->unsignedBigInteger('compte_destinataire_id')->nullable();
            $table->unsignedBigInteger('type_transaction_id');
            $table->decimal('montant', 15, 2);
            $table->decimal('montant_debite', 15, 2)->nullable();
            $table->decimal('montant_credite', 15, 2)->nullable();
            $table->boolean('frais')->default(false);
            $table->decimal('montant_frais', 15, 2)->nullable();
            $table->enum('frais_paye_par', ['emetteur', 'destinataire'])->nullable();
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->enum('frequence', ['monthly', 'everyminute', 'everyday', 'weekly'])->default('monthly');
            $table->dateTime('date_debut');
            $table->boolean('active')->default(true);
            $table->enum('statut', ['PLANIFIE', 'EXECUTE', 'ANNULE'])->default('PLANIFIE');
            $table->timestamps();

            // Clés étrangères
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('compte_destinataire_id')->references('id')->on('comptes')->onDelete('set null');
            $table->foreign('type_transaction_id')->references('id')->on('types_transactions')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('utilisateurs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('transactions_planifiees');
    }
};