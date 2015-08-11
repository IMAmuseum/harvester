<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActorDateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actor_date', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('actor_id')->unsigned()->index();
            $table->foreign('actor_id')->references('id')->on('actors')->onDelete('cascade');
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
        Schema::drop('actor_date');
    }
}
