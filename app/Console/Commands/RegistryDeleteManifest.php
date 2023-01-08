<?php

namespace App\Console\Commands;

use App\Models\ManifestMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RegistryDeleteManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registry:delete-manifest
        {container : The container whose manifest should be deleted}
        {--untagged : Delete untagged manifests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove image manifests from the local registry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $container = $this->argument('container');
        $drive = Storage::drive('s3');

        if($this->option('untagged')){
            $this->info('Fetching untagged manifests');

            $manifests = ManifestMetadata::doesntHave('tags')
                ->with('tags')
                ->where('registry', null)
                ->where('container', $container)
                ->get();

            if($manifests->isEmpty()){
                $this->error("No manifest found for container $container");
                return Command::FAILURE;
            }
        } else {
            /** @var Collection<ManifestMetadata> $choices */
            $choices = ManifestMetadata::with('tags')
                ->where('registry', null)
                ->where('container', $container)
                ->orderByDesc('created_at')
                ->get()
                ->mapWithKeys(function (ManifestMetadata $item, $key) {
                    $tags = $item->tags->map->tag->join(', ');
                    return [
                        $item->id => $item->docker_hash . (strlen($tags) > 0 ? ' -> ' . $tags : '')
                    ];
                });

            if($choices->isEmpty()){
                $this->error("No manifest found for container $container");
                return Command::FAILURE;
            }

            $choices->put('*', '(Everything)');

            $selection = $this->choice('Which tags to delete ?', $choices->toArray(), null, null, true);

            $this->info('Fetching manifests to delete');
            $manifests = ManifestMetadata::with('tags')
                ->where('container', $container);
            if($selection[0] != '*'){
                $manifests = $manifests->whereIn('docker_hash', $selection);
            }
            $manifests = $manifests->get();
        }

        foreach($manifests as $manifest){
            $affected_layers = $manifest->layers()->detach();
            $affected_tags = $manifest->tags()->delete();
            $manifest->delete();
            $this->info("Deleted manifest $manifest->docker_hash, detached $affected_layers layers and deleted $affected_tags tags");
            $drive->delete("repository/$container/manifests/$manifest->docker_hash");
        }

        return Command::SUCCESS;
    }
}
