<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('contract_name');
            $table->string('network')->defaut('bnb');
            $table->string('contract_address')->unique()->nullable();
            $table->string('contract_symbol');
            $table->string('contract_decimals')->nullable();
            $table->string('image')->default('defaults/bnb_logo.png');
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
        Schema::dropIfExists('contracts');
    }
};
