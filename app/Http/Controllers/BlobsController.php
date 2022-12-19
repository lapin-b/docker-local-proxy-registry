<?php

namespace App\Http\Controllers;

use App\Models\PendingContainerLayer;
use Illuminate\Http\Request;
use Ulid\Ulid;

class BlobsController extends Controller
{
    public function initiateUpload(Request $request, string $container_ref){
        $pending_container_layer = new PendingContainerLayer();
        $pending_container_layer->id = Ulid::generate();
        $pending_container_layer->container_reference = $container_ref;
        $pending_container_layer->save();

        return response('', 202)
            ->header(
                'Location',
                route('blobs.process_upload', compact('pending_container_layer', 'container_ref'))
            )
            ->header('Docker-Upload-UUID', $pending_container_layer->id);
    }
}
