<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Jobs\ProxyRegistry\PushContainerLayerJob;
use App\Lib\DockerClient\Client;
use App\Lib\RegistryStorage\ContainerLayer;
use App\Lib\RegistryStorage\RegistryStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlobsController extends Controller
{
    public function __construct(
        public RegistryStorage $storage
    ) {

    }

    public function get_blob(Request $request, $registry, $container_ref, $blob_ref)
    {
        $existing_layer = $this->storage->fetch_container_layer($blob_ref);
        $logger = Log::withContext(['container_ref' => $container_ref, 'registry' => $registry, 'blob' => $blob_ref]);

        if (!is_null($existing_layer)) {
            $logger->info("Blob exists in cache");

            $headers = [
                'Docker-Content-Digest' => 'sha256:' . $existing_layer->hash,
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => $existing_layer->size(),
                'Docker-Proxy-Cache' => 'HIT'
            ];

            return response()->file($existing_layer->absolute_path(), $headers);
        }

        $logger->info("Serving from remote");
        $client = new Client($registry, $container_ref);
        $client->authenticate();
        $remote_layer_response = $client->get_blob($blob_ref);

        return response()
            ->stream(
                function() use ($remote_layer_response, $blob_ref) {
                    $local_cached_layer = ContainerLayer::make($blob_ref);
                    $remote_layer_stream = $remote_layer_response->toPsrResponse()->getBody();

                    $local_cached_layer_fh = fopen($local_cached_layer->absolute_path(), 'w');
                    while(!$remote_layer_stream->eof()){
                        $chunk = $remote_layer_stream->read(16*1024);
                        fputs($local_cached_layer_fh, $chunk);
                        echo $chunk;
                        flush();
                    }
                    fclose($local_cached_layer_fh);
                },
                200,
                [
                    'Docker-Content-Digest' => $blob_ref,
                    'Content-Type' => 'application/octet-stream',
                    'Content-Length' => $remote_layer_response->header('Content-Size'),
                    'Docker-Proxy-Cache' => 'MISS',
                ]
            );
    }
}
