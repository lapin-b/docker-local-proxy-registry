<?php

namespace App\Http\Controllers\Traits;

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;
use App\Models\ManifestTag;

trait ProcessesDockerManifests
{
    protected function createManifestAndLinkedTag(
        string $manifest_reference, string $container,
        ?string $registry, string $content_type,
        int $manifest_size, string $manifest_hash
    ): ManifestMetadata {
        $db_manifest = ManifestMetadata::updateOrCreate(
            [
                'docker_hash' => $manifest_hash,
                'container' => $container,
                'registry' => $registry,
            ],
            [
                'content_type' => $content_type,
                'size' => $manifest_size
            ]
        );

        if (!str_starts_with($manifest_reference, 'sha256:')) {
            ManifestTag::updateOrInsert(
                [
                    'container' => $container,
                    'tag' => $manifest_reference,
                    'registry' => $registry,
                ],
                [
                    'manifest_metadata_id' => $db_manifest->id
                ]
            );
        }

        return $db_manifest;
    }

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
