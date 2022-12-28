<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('docker_registry_clients', function (Blueprint $table) {
            $table->id();
            $table->string('registry');
            $table->string('container');
            $table->dateTimeTz('issued_at');
            $table->dateTimeTz('expires_at');
            $table->integer('validity_time');
            $table->text('access_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('docker_registry_clients');
    }
};
