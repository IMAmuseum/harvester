<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('object_uid')->unique()->nullable();
            $table->string('object_title')->nullable();
            $table->string('object_name')->nullable();
            $table->text('object_desc')->nullable();
            $table->string('accession_num')->nullable();
            $table->string('accession_date')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('medium_display')->nullable();
            $table->string('created_date')->nullable();
            $table->string('created_location')->nullable();
            $table->string('country')->nullable();
            $table->string('culture')->nullable();
            $table->string('collection')->nullable();
            $table->string('department')->nullable();
            $table->text('provenance')->nullable();
            $table->text('inscription')->nullable();
            $table->text('rights')->nullable();
            $table->text('credit_line')->nullable();
            $table->integer('deaccession_id')->nullable()->unsigned();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->boolean('publish_web')->default(0);
            $table->boolean('can_zoom')->default(0);
            $table->boolean('can_download')->default(0);
            $table->string('protected_size')->nullable();
            $table->boolean('on_view')->default(0);
            $table->boolean('curator_verified')->default(0);
            $table->text('object_custom')->nullable();
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
        Schema::drop('objects');
    }
}
