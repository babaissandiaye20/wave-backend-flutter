<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompteDestinataireIdToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('compte_destinataire_id')->nullable();
            $table->foreign('compte_destinataire_id')
                  ->references('id')
                  ->on('comptes')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['compte_destinataire_id']);
            $table->dropColumn('compte_destinataire_id');
        });
    }
}