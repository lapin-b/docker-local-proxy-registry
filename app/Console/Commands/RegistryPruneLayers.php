<?php

namespace App\Console\Commands;

use App\Models\ContainerLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegistryPruneLayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registry:prune-layers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = /** @lang text */ 'Delete unused layers files from the container storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Searching unused layers in the database');
        $layers = ContainerLayer::doesntHave('manifests')
            ->where('registry', null)
            ->get();

        $used_size = $layers->map->size->sum();
        $this->info('Found ' . $layers->count() . ' unused layers, using ' . $this->human_size($used_size));

        $drive = Storage::drive('s3');

        foreach($layers as $layer){
            $drive->delete("repository/$layer->container/blobs/$layer->docker_hash");
            $layer->delete();
            $this->info("Deleted layer $layer->docker_hash from $layer->container");
        }

        return Command::SUCCESS;
    }

    private function human_size(int $size){
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $exponent = min(floor(log($size, 1000)), count($units) - 1);
        $unit = $units[$exponent];

        return number_format($size / pow(1000, $exponent), 2, '.', "'") . ' ' . $unit;
    }
}
