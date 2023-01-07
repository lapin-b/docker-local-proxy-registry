<?php

namespace App\Console\Commands\OneShot;

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportManifestLayerRelationships extends Command
{
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
            $manifest_file = $drive->get(
                !empty($manifest->registry)
                    ? "proxy/$manifest->registry/$manifest->container/manifests/$manifest->docker_hash"
                    : "repository/$manifest->container/manifests/$manifest->docker_hash"
            );

            $manifest_content = json_decode($manifest_file, true);
            $manifest_layers = collect($manifest_content['layers']);
            $manifest_layers->push($manifest_content['config']);

            $layers_to_sync_to_manifest = [];
            foreach($manifest_layers as $layer){
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
                    $layer_path = !empty($manifest->registry)
                        ? "proxy/$manifest->registry/$manifest->container/blobs/$layer_hash"
                        : "repository/$manifest->container/blobs/$layer_hash";

                    if(!$drive->exists($layer_path)){
                        $this->error("Layer $layer_hash does not exist in the repository. This indicates a broken repository for this image or a broken database. Skipping");
                        continue;
                    }

                    $db_layer->save();
                }

                $layers_to_sync_to_manifest[] = $db_layer->id;
            }

            $manifest->layers()->sync($layers_to_sync_to_manifest);
            $manifest->save();
        }

        return Command::SUCCESS;
    }
}
