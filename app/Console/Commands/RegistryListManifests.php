<?php

namespace App\Console\Commands;

use App\Models\ManifestMetadata;
use App\Models\ManifestTag;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RegistryListManifests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registry:list-manifests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists manifests present in the registry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $containers = ManifestMetadata::distinct()
            ->orderBy('registry')
            ->orderBy('container')
            ->get(['registry', 'container']);

        foreach($containers as $container) {
            $this->line($container->registry . '/' . $container->container);

            /** @var Collection<ManifestMetadata> $manifests */
            $manifests = ManifestMetadata::with('tags')
                ->orderByDesc('created_at')
                ->where('registry', $container->registry)
                ->where('container', $container->container)
                ->get();

            foreach($manifests as $manifest){
                $tags = $manifest->tags->map->tag->join(', ');
                $this->output->writeln(
                    "\t" .
                    $manifest->docker_hash .
                    (strlen($tags) > 0 ? ' -> ' . $tags : '')
                );
            }
        }

        return Command::SUCCESS;
    }
}
