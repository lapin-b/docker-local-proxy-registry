<?php

namespace App\Http\Controllers;

use App\Models\PendingContainerLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ulid\Ulid;

class BlobsController extends Controller
{
    public function initiateUpload(Request $request, string $container_ref){
        if($request->query('digest') != null){
            return response('Monolithic uploads are not implemented', 501);
        }

        $pending_container_layer = new PendingContainerLayer();
        $pending_container_layer->id = Ulid::generate();
        $pending_container_layer->container_reference = $container_ref;
        $pending_container_layer->save();

        return response('', 202)
            ->header(
                'Location',
                route('blobs.process_upload', compact('pending_container_layer', 'container_ref'))
            )
            ->header('Range', '0-0')
            ->header('Docker-Upload-UUID', $pending_container_layer->id);
    }

    public function processPartialUpload(
        Request $request,
        string $container_ref,
        PendingContainerLayer $pending_container_layer
    ){
        if($pending_container_layer->container_reference != $container_ref){
            return response('', 403);
        }

        $body = $request->getContent(true);
        $local_storage = Storage::disk('local');
        $local_storage->makeDirectory('uploads');
        $local_storage->writeStream('uploads/' . $pending_container_layer->id, $body);
        $file_size = $local_storage->size('uploads/' . $pending_container_layer->id);

        return response('', 202)
            ->header('Range', "0-$file_size")
            ->header('Docker-Upload-UUID', $pending_container_layer->id);
    }
}
