<?php

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;
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
        Schema::create('container_layer_manifest_metadata', function (Blueprint $table) {
            $table->foreignId('container_layer_id')->constrained('container_layers');
            $table->foreignId('manifest_metadata_id')->constrained('manifest_metadata');
            $table->primary(['container_layer_id', 'manifest_metadata_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('container_layer_manifest_metadata');
    }
};
