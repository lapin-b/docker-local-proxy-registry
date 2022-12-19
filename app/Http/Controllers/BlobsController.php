<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlobsController extends Controller
{
    public function get_blob($container_ref, $blob_ref) {
        $blob_location = "repository/$container_ref/blobs/$blob_ref";
        $storage = Storage::drive('local');

        if($storage->fileMissing($blob_location)){
            // TODO: Return proper Docker response
            return response('', 404);
        }

        $file_size = $storage->size($blob_location);
        $hash = hash_file('sha256', $storage->path($blob_location));

        return response()->file(
            $storage->path($blob_location),
            [
                'Docker-Content-Digest' => "sha256:$hash",
                'Content-Length' => $file_size
            ]
        );
    }
}
