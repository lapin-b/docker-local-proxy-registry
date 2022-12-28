<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Jobs\ProxyRegistry\PushContainerLayerJob;
use App\Lib\DockerClient\Client;
use App\Models\ContainerLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlobsController extends Controller
{
    public function get_blob(Request $request, $registry, $container_ref, $blob_ref)
    {
        $existing_blob = ContainerLayer::where('registry', $registry)
            ->where('container', $container_ref)
            ->where('docker_hash', $blob_ref)
            ->first();
        $logger = Log::withContext(['container_ref' => $container_ref, 'registry' => $registry, 'blob' => $blob_ref]);

        if (!is_null($existing_blob)) {
            $logger->info("Blob exists in cache");
            // We can serve the existing blob in the database, be it from the S3 bucket or the temporary file
            $headers = [
                'Docker-Content-Digest' => $existing_blob->docker_hash,
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => $existing_blob->size,
                'Docker-Proxy-Cache' => 'S3'
            ];

            if (is_null($existing_blob->temporary_filename)) {
                $logger->info("Serving from S3");
                return Response::redirectTo(
                    Storage::drive('s3')->temporaryUrl(
                        "proxy/$existing_blob->registry/$existing_blob->container/blobs/$existing_blob->docker_hash",
                        now()->addMinutes(5)
                    ),
                    307,
                    $headers
                );
            }

            $logger->info("Serving from local");
            $headers['Docker-Proxy-Cache'] = 'LOCAL';
            $file_handle = fopen(Storage::drive('local')->path("push/$existing_blob->temporary_filename"), 'r');

            return response()->stream(
                function() use ($file_handle) {
                    while(!feof($file_handle)) {
                        echo fgets($file_handle, 16 * 1024);
                        flush();
                    }
                },
                200,
                $headers
            );
        }

        $logger->info("Serving from remote");
        $client = new Client($registry, $container_ref);
        $client->authenticate();
        $layer_response = $client->get_blob($blob_ref);

        // Dump the blob into a temporary file first, a job will take over for the pushing
        $layer_ulid = Str::ulid()->toBase32();
        $layer_storage = Storage::drive('local');
        $layer_storage->makeDirectory('push');
        $layer_local_path = $layer_storage->path("push/$layer_ulid");

        $database_layer = ContainerLayer::firstOrNew([
            'docker_hash' => $blob_ref,
            'container' => $container_ref,
            'registry' => $registry,
        ], [
            'size' => $layer_response->header('Content-Length'),
            'temporary_filename' => $layer_ulid
        ]);

        return response()
            ->stream(
                function() use ($database_layer, $layer_local_path, $layer_response){
                    $layer_stream = $layer_response->toPsrResponse()->getBody();
                    $temp_fh = fopen($layer_local_path, 'w');
                    while(!$layer_stream->eof()){
                        $chunk = $layer_stream->read(16*1024);
                        fputs($temp_fh, $chunk);
                        echo $chunk;
                        flush();
                    }
                    fclose($temp_fh);
                    $database_layer->save();
                    PushContainerLayerJob::dispatch($database_layer);
                },
                200,
                [
                    'Docker-Content-Digest' => $blob_ref,
                    'Content-Type' => 'application/octet-stream',
                    'Content-Length' => $database_layer->size,
                    'Docker-Proxy-Cache' => 'MISS',
                ]
            );
    }
}
