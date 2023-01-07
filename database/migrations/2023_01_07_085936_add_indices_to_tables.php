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
        Schema::table('container_layers', function (Blueprint $table) {
            $table->index(['registry', 'container', 'docker_hash']);
        });

        Schema::table('docker_registry_clients', function(Blueprint $table){
            $table->index(['registry', 'container', 'expires_at']);
        });

        Schema::table('manifest_metadata', function(Blueprint $table){
            $table->index(['registry', 'container']);
        });

        Schema::table('manifest_tags', function(Blueprint $table){
            $table->index(['registry', 'container', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('container_layers', function (Blueprint $table) {
            $table->dropIndex(['registry', 'container', 'docker_hash']);
        });

        Schema::table('docker_registry_clients', function(Blueprint $table){
            $table->dropIndex(['registry', 'container', 'expires_at']);
        });

        Schema::table('manifest_metadata', function(Blueprint $table){
            $table->dropIndex(['registry', 'container']);
        });

        Schema::table('manifest_tags', function(Blueprint $table){
            $table->dropIndex(['registry', 'container', 'tag']);
        });
    }
};
