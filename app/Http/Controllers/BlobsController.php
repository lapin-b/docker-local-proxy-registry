<?php

namespace App\Http\Controllers;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToRetrieveMetadata;

class BlobsController extends Controller
{
    public function get_blob(Request $request, $container_ref, $blob_ref) {
        $blob_location = "repository/$container_ref/blobs/$blob_ref";
        $storage = Storage::drive('s3');

        if($storage->fileMissing($blob_location)){
            return response(new DockerRegistryErrorBag(DockerRegistryError::unknown_blob($blob_ref, $container_ref)), 404);
        }

        $file_size = $storage->size($blob_location);

        if($request->method() == 'HEAD'){
            return \response('', 200)
                ->header('Content-Length', $file_size);
        }

        return Response::redirectTo(
            $storage->temporaryUrl(
                $blob_location,
                now()->addMinutes(5),
                [
                    'Response-Content-Type' => 'application/stream'
                ]
            ), 307
        );
//        return Response::stream(function() use($file_stream_handle){
//            while(!feof($file_stream_handle)){
//                echo fgets($file_stream_handle, 64*1024);
//                flush();
//            }
//        }, 200, [
//            'Docker-Content-Digest' => "sha256:$hash",
//            'Content-Length' => $file_size,
//            'Content-Type' => 'application/octet-stream'
//        ]);
    }
}
