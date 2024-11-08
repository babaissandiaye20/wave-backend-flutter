<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compte_id'); // ID du compte associé à la transaction
            $table->unsignedBigInteger('type_transaction_id'); // Type de transaction
            $table->decimal('montant', 15, 2); // Montant de la transaction
            $table->timestamps();

            // Clés étrangères vers les tables comptes et types_transactions
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('type_transaction_id')->references('id')->on('types_transactions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}