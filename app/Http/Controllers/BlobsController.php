<?php

namespace App\Http\Controllers;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class BlobsController extends Controller
{
    public function get_blob($container_ref, $blob_ref) {
        $blob_location = "repository/$container_ref/blobs/$blob_ref";
        $storage = Storage::drive('local');

        if($storage->fileMissing($blob_location)){
            // TODO: Return proper Docker response
            return response(new DockerRegistryErrorBag(DockerRegistryError::unknown_blob($blob_ref, $container_ref)), 404);
        }

        $file_size = $storage->size($blob_location);
        $file_stream_handle = $storage->readStream($blob_location);
        $hash = hash_file('sha256', $storage->path($blob_location));

        return Response::stream(function() use($file_stream_handle){
            while(!feof($file_stream_handle)){
                echo fgets($file_stream_handle, 64*1024);
                flush();
            }
        }, 200, [
            'Docker-Content-Digest' => "sha256:$hash",
            'Content-Length' => $file_size,
            'Content-Type' => 'application/octet-stream'
        ]);
    }
}
