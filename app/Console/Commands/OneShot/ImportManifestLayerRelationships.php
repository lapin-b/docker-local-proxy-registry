<?php

namespace App\Console\Commands\OneShot;

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class ImportManifestLayerRelationships extends Command
{
    const REGISTRY_OBJECT_BLOBS = 'blobs';
    const REGISTRY_OBJECT_MANIFESTS = "manifests";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oneshot:registry:import-layer-rel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Oneshot command to import container <-> layer relationships from storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $drive = Storage::drive('s3');
        $manifests = ManifestMetadata::where(
            'content_type',
            '=',
            'application/vnd.docker.distribution.manifest.v2+json'
        )->get();

        foreach($manifests as $manifest){
            $this->info("Fetching manifest $manifest->registry/$manifest->container ($manifest->docker_hash)");
            $manifest_file = $drive->get($this->_objectPath(
                self::REGISTRY_OBJECT_MANIFESTS,
                $manifest->registry,
                $manifest->container,
                $manifest->docker_hash
            ));

            $manifest_content = json_decode($manifest_file, true);
            $manifest_layers = collect($manifest_content['layers']);
            $manifest_layers->push($manifest_content['config']);

            $layers_to_sync_to_manifest = [];

            foreach($manifest_layers as $layer){
                $db_layer = $this->_insertOrCreateImageLayer($layer, $manifest, $drive);
                $layers_to_sync_to_manifest[] = $db_layer->id ?? -1;
            }

            $manifest->layers()->sync(array_filter($layers_to_sync_to_manifest, fn($i) => $i != -1));
            $manifest->save();
        }

        return Command::SUCCESS;
    }

    private function _insertOrCreateImageLayer(
        array             $layer,
        ManifestMetadata  $manifest,
        FilesystemAdapter $drive
    ): ?ContainerLayer {
        $layer_hash = $layer['digest'];
        $layer_size = $layer['size'];

        $this->info("Attaching layer $layer_hash to the manifest");

        $db_layer = ContainerLayer::firstOrNew([
            'docker_hash' => $layer_hash,
            'container' => $manifest->container,
            'registry' => $manifest->registry,
        ], [
            'size' => $layer_size
        ]);

        if(!$db_layer->exists){
            $this->warn("Layer $layer_hash does not exist in the database. Checking storage");
            $layer_path = $this->_objectPath(
                self::REGISTRY_OBJECT_BLOBS,
                $manifest->registry,
                $manifest->container,
                $layer_hash
            );

            if(!$drive->exists($layer_path)){
                $this->error("Layer $layer_hash does not exist in the repository. This indicates a broken repository for the manifest or a broken database. Skipping");
                return null;
            }

            $db_layer->save();
        }

        return $db_layer;
    }

    private function _objectPath(string $objectType, ?string $registry, string $container, string $hash){
        return empty($registry)
            ? "repository/$container/$objectType/$hash"
            : "proxy/$registry/$container/$objectType/$hash";
    }
}
