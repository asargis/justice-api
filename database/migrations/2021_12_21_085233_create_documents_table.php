<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('appeal_id');
            $table->string('name');
            $table->string('path');
            $table->string('description', 255)->nullable();
            $table->integer('page_count');
            $table->enum('type', ['proxy', 'essence', 'attachment', 'payment']);
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
        Schema::dropIfExists('documents');
    }
}
