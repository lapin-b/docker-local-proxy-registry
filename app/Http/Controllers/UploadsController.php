<?php

namespace App\Http\Controllers;

use App\Models\PendingContainerLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\MountManager;
use Ulid\Ulid;

class UploadsController extends Controller
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

    public function process_partial_update(
        Request $request,
        string $container_ref,
        string $upload_ref
    ){
        $pending_container_layer = PendingContainerLayer::findOrFail($upload_ref);
        if($pending_container_layer->container_reference != $container_ref){
            return response('', 403);
        }

        $upload_path = 'uploads/' . $pending_container_layer->id;
        $upload_root = dirname($upload_path);

        $body = $request->getContent(true);
        $fs = Storage::disk('local');
        $fs->makeDirectory($upload_root);
        $absolute_upload_path = $fs->path($upload_path);

        // We need to bypass the Storage abstraction because calling "append"
        // on it causes the original file to be read, then written back. Silly,
        // I know.
        $temporary_upload_file = fopen($absolute_upload_path, 'a');
        stream_copy_to_stream($body, $temporary_upload_file);
        $file_size = $fs->size($upload_path);

        if($request->method() == 'PUT') {
            $docker_hash = $request->query('digest');
            $s3 = Storage::disk('s3');

            $final_storage_path = "repository/$pending_container_layer->container_reference/blobs/$docker_hash";
            $mount_manager = new MountManager([
                's3' => Storage::disk('s3')->getDriver(),
                'local' => Storage::disk('local')->getDriver()
            ]);
            $mount_manager->move('local://' . $upload_path, 's3://' . $final_storage_path);

            $pending_container_layer->delete();

            return response('', 201)
                ->header(
                    'Location',
                    $s3->temporaryUrl(
                        $final_storage_path,
                        now()->addMinutes(5),
                        [
                            'Docker-Content-Digest' => $docker_hash
                        ]
                    )
                );
        }

        return response('', 202)
            ->header('Range', "0-$file_size")
            ->header('Docker-Upload-UUID', $pending_container_layer->id);
    }
}
