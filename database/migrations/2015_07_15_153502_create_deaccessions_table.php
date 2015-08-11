<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeaccessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deaccessions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('deaccesion_type')->nullable();
            $table->string('deaccesion_desc')->nullable();
            $table->string('deaccesion_date')->nullable();
            $table->timestamp('deaccesion_date_at')->nullable();
            $table->text('deaccession_custom')->nullable();
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
        Schema::drop('deaccessions');
    }
}
