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
        $manifests = ManifestMetadata::where('content_type', '=', 'application/vnd.docker.distribution.manifest.v2+json')
            ->get();

        foreach($manifests as $manifest){
            $this->info("Fetching manifest $manifest->registry/$manifest->container ($manifest->docker_hash)");
            $manifest_file = $drive->get(
                !empty($manifest->registry)
                    ? "proxy/$manifest->registry/$manifest->container/manifests/$manifest->docker_hash"
                    : "repository/$manifest->container/manifests/$manifest->docker_hash"
            );

            $manifest_content = json_decode($manifest_file, true);
            $manifest_layers = collect($manifest_content['layers'])->map(fn($layer) => $layer['digest']);
            $manifest_layers->push($manifest_content['config']['digest']);

            $layers_to_sync_to_manifest = [];
            foreach($manifest_layers as $layer){
                $this->comment("Attaching layer $layer to the manifest");
                $db_layer = ContainerLayer::where('registry', $manifest->registry)
                    ->where('container', $manifest->container)
                    ->where('docker_hash', $layer)
                    ->firstOrCreate();

                if($db_layer == null){
                    $this->error("Couldn't find layer $layer in the database. The image might not be completely in cache or the database is broken.");
                    continue;
                }

                $layers_to_sync_to_manifest[] = $db_layer->id;
            }

            $manifest->layers()->sync($layers_to_sync_to_manifest);
            $manifest->save();
        }

        return Command::SUCCESS;
    }
}
