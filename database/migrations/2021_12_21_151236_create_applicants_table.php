<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('appeal_id');
            $table->string('name', 255);
            $table->string('inn', 255);
            $table->string('procedural_status', 255)->nullable();
            $table->string('ogrn', 255)->nullable();
            $table->string('kpp', 255)->nullable();
            $table->string('legal_zipcode', 255);
            $table->string('legal_address', 255);
            $table->string('location_zipcode', 255);
            $table->string('location_address', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->timestamps();

            $table->foreign('appeal_id')
                ->references('id')
                ->on('appeals')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applicants');
    }
}
