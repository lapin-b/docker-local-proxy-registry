<?php

namespace App\Console\Commands\ProxyRegistry;

use App\Jobs\ProxyRegistry\PushContainerLayerJob;
use App\Models\ContainerLayer;
use Illuminate\Console\Command;

class QueuePushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:queue-push {layers*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues a layer push job';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $layers = $this->argument('layers');

        foreach ($layers as $layer){
            $container_layer = ContainerLayer::whereTemporaryFilename($layer)->first();
            if(is_null($container_layer)){
                $this->error("Container layer with temporary file $layer not found in the database");
                continue;
            }

            PushContainerLayerJob::dispatch($container_layer);
            $this->info("Dispatched push job for layer $layer ($container_layer->registry/$container_layer->container)");
        }

        return Command::SUCCESS;
    }
}
