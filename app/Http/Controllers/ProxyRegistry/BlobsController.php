<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Lib\DockerClient\Client;
use App\Models\ContainerLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlobsController extends Controller
{
    public function get_blob(Request $request, $registry, $container_ref, $blob_ref){
        $existing_blob = ContainerLayer::where('registry', $registry)
            ->where('container', $container_ref)
            ->where('docker_hash', $blob_ref)
            ->first();

        if(!is_null($existing_blob)){
            // We can serve the existing blob in the database, be it from the S3 bucket or the temporary file
            return response('', 501);
        }

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
                },
                200,
                [
                    'Docker-Content-Digest' => $blob_ref,
                    'Content-Type' => 'application/octet-stream',
                    'Docker-Proxy-Cache' => 'MISS'
                ]
            );
    }
}
