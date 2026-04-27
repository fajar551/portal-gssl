<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCbmsConvenienceFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cbms_convenience_fees', function (Blueprint $table) {
            $table->id();
            $table->string('paymentmethod')->nullable();
            $table->integer('fixed_amount')->nullable();
            $table->integer('percentage_amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cbms_convenience_fees');
    }
}
