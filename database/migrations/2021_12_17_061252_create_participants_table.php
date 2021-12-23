<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('appeal_id');
            $table->string('procedural_status', 255)->nullable();
            $table->string('first_name', 255);
            $table->string('middle_name', 255)->nullable();
            $table->string('last_name', 255);
            $table->date('birthday')->nullable();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->string('birthplace', 255)->nullable();
            $table->string('registration_zipcode', 6);
            $table->string('registration_address', 255);
            $table->string('resident_zipcode', 6);
            $table->string('resident_address', 255);
            $table->string('snils', 11);
            $table->string('inn', 12);
            $table->string('identity_type', 255)->nullable();
            $table->string('passport_series', 32)->nullable();
            $table->string('passport_number', 32)->nullable();
            $table->date('passport_issued_date')->nullable();
            $table->string('passport_issued_by')->nullable();

            $table->string('drivers_license_series')->nullable();
            $table->string('drivers_license_number')->nullable();

            $table->string('vehicle_series')->nullable();
            $table->string('vehicle_number')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
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
        Schema::dropIfExists('participants');
    }
}
