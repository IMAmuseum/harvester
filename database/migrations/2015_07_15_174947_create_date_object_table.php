<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDateObjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('date_object', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('object_id')->unsigned()->index();
            $table->foreign('object_id')->references('id')->on('objects')->onDelete('cascade');
            $table->integer('date_id')->unsigned()->index();
            $table->foreign('date_id')->references('id')->on('dates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('date_object');
    }
}
