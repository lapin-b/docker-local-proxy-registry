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
        Schema::create('manifest_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_reference');
            $table->string('container_reference');
            $table->string('docker_hash');
            $table->string('content_type');
            $table->string('proxied_registry')->nullable();
            $table->integer('filesize');
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
        Schema::dropIfExists('manifest_metadata');
    }
};
