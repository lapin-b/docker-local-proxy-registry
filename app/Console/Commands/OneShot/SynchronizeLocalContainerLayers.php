<?php

namespace App\Console\Commands\OneShot;

use App\Models\ContainerLayer;
use App\Models\ManifestMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SynchronizeLocalContainerLayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oneshot:registry:sync-layers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists layer files from the S3 storage for the local registry and inserts the information into the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $local_manifests = ManifestMetadata::select(['container'])
            ->distinct()
            ->whereNull('registry')
            ->get();

        $storage = Storage::drive('s3');

        foreach($local_manifests as $manifest){
            $container = $manifest['container'];
            $this->info("Listing and inserting layers for $container");
            $file_list = $storage->listContents("repository/$container/blobs")->toArray();

            foreach($file_list as $file){
                $layer = basename($file->path());
                $this->comment("Inserting layer $layer");

                ContainerLayer::updateOrCreate([
                    'docker_hash' => $layer,
                    'container' => $container,
                    'registry' => null,
                ],
                [
                    'size' => $file['fileSize'],
                    'temporary_filename' => null
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
