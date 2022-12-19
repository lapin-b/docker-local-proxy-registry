<?php

namespace App\Http\Controllers;

use App\Models\PendingContainerLayer;
use Illuminate\Http\Request;
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
}
