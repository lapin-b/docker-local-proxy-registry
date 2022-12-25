<?php

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
        Schema::create('manifest_tags', function (Blueprint $table) {
            $table->id();
            $table->string('container');
            $table->string('registry')->nullable();
            $table->timestamps();

            $table->foreignIdFor(ManifestMetadata::class)
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manifest_tags');
    }
};
