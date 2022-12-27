<?php

namespace App\Jobs\ProxyRegistry;

use App\Models\ContainerLayer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\MountManager;

class PushContainerLayerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ContainerLayer $layer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ContainerLayer $layer)
    {
        $this->layer = $layer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mount = new MountManager([
            'local' => Storage::drive('local')->getDriver(),
            's3' => Storage::drive('s3')->getDriver()
        ]);

        $mount->copy(
            "local://push/{$this->layer->temporary_filename}",
            "s3://proxy/{$this->layer->registry}/{$this->layer->container}/blobs/{$this->layer->docker_hash}"
        );

        $mount->delete("local://push/{$this->layer->temporary_filename}");
        $this->layer->temporary_filename = null;
        $this->layer->save();
    }
}
