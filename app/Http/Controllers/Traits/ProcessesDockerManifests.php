<?php

namespace App\Http\Controllers\Traits;

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;

trait ProcessesDockerManifests
{
    protected function syncLayerRelationships(string $manifest_content, ManifestMetadata $manifest){
        $unserialized_manifest = json_decode($manifest_content, true);

        // Postprocessing
        $layers = collect($unserialized_manifest['layers'])
            ->add($unserialized_manifest['config']);

        foreach($layers as $layer){
            $db_layer = ContainerLayer::where('registry', $manifest->registry)
                ->where('container', $manifest->container)
                ->where('docker_hash', $layer['digest'])
                ->firstOrFail();
            $manifest->layers()->attach($db_layer);
        }

        $manifest->save();
    }
}
