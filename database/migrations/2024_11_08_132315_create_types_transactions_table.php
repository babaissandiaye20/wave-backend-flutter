<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('types_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Nom du type de transaction
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('types_transactions');
    }
}