<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('actor_uid')->unique();
            $table->string('actor_name_display');
            $table->string('actor_name_first')->nullable();
            $table->string('actor_name_last')->nullable();
            $table->string('actor_name_middle')->nullable();
            $table->string('actor_name_suffix')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('birth_location')->nullable();
            $table->string('work_location')->nullable();
            $table->string('death_date')->nullable();
            $table->string('death_location')->nullable();
            $table->string('actor_nationality')->nullable();
            $table->text('actor_custom')->nullable();
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
        Schema::drop('actors');
    }
}
