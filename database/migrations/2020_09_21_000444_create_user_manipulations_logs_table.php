<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserManipulationsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_manipulations_logs', function (Blueprint $table) {
            $table->Increments('id')->unsigned();
            $table->string('action',16);
            $table->integer('entity_id')->unsigned();
            $table->json('original_values')->nullable();
            $table->string('new_values')->nullable();
            $table->string('by_user');
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
        Schema::dropIfExists('user_manipulations_logs');
    }
}
