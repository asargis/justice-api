<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable();
            $table->string('esia_login');
            $table->string('esia_password');
            $table->string('selenium_url');
            $table->enum('type', ['administrative', 'civil']);
            $table->string('birthplace');
            $table->string('court_region');
            $table->string('court_judiciary');
            $table->enum('status', ['new', 'processing', 'failed', 'processed'])->default('new');
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
        Schema::dropIfExists('appeals');
    }
}
