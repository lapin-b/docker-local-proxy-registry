<?php

namespace App\Http\Controllers;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Models\PendingContainerLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\MountManager;
use Symfony\Component\Uid\Ulid;

class UploadsController extends Controller
{
    public function initiateUpload(Request $request, string $container_ref){
        $logger = Log::withContext(['container_ref' => $container_ref]);

        if($request->query('digest') != null){
            return response('Monolithic uploads are not supported', 501);
        }

        $logger->info("Creating upload");
        $pending_container_layer = new PendingContainerLayer([
            'container_reference' => $container_ref,
        ]);
        $pending_container_layer->id = Ulid::generate();
        $pending_container_layer->rel_upload_path = "uploads/$pending_container_layer->id";
        $pending_container_layer->save();
        $logger->info("Created upload $pending_container_layer->id");

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

        $logger = Log::withContext(['container_ref' => $container_ref, 'upload' => $upload_ref]);

        $body = $request->getContent(true);
        $fs = Storage::disk('local');
        $fs->makeDirectory(dirname($pending_container_layer->rel_upload_path));
        $absolute_upload_path = $fs->path($pending_container_layer->rel_upload_path);

        // We need to bypass the Storage abstraction because calling "append"
        // on it causes the original file to be read, then written back. Silly,
        // I know.
        $logger->info("Copying upload body stream");
        $temporary_upload_file = fopen($absolute_upload_path, 'a');
        stream_copy_to_stream($body, $temporary_upload_file);
        fclose($temporary_upload_file);

        if($request->method() == 'PUT') {
            $logger->info("Finalization requested, sending layer on the S3 bucket");
            $docker_hash = $request->query('digest');
            $s3 = Storage::disk('s3');

            $final_storage_path = "repository/$pending_container_layer->container_reference/blobs/$docker_hash";
            $mount_manager = new MountManager([
                's3' => Storage::disk('s3')->getDriver(),
                'local' => Storage::disk('local')->getDriver()
            ]);
            $mount_manager->move(
                'local://' . $pending_container_layer->rel_upload_path,
                's3://' . $final_storage_path
            );
            $pending_container_layer->delete();

            $logger->info("Redirecting the client to the S3 bucket");
            return response('', 201)
                ->header(
                    'Location',
                    $s3->temporaryUrl(
                        $final_storage_path,
                        now()->addMinutes(5),
                    )
                )
                ->header('Docker-Content-Digest', $docker_hash);
        }

        $file_size = $fs->size($pending_container_layer->rel_upload_path);
        $logger->info("Upload incomplete, not finalizing");
        return response('', 202)
            ->header(
                'Location',
                route('blobs.process_upload', compact('pending_container_layer', 'container_ref'))
            )
            ->header('Range', "0-$file_size")
            ->header('Docker-Upload-UUID', $pending_container_layer->id);
    }

    public function cancel_upload(
        Request $request,
        string $container_ref,
        string $upload_ref
    ){
        $pending_container_layer = PendingContainerLayer::findOrFail($upload_ref);
        if($pending_container_layer->container_reference != $container_ref){
            return response('', 403);
        }
        $logger = Log::withContext(['container_ref' => $container_ref, 'upload' => $upload_ref]);

        $logger->info("$upload_ref $container_ref Canceling upload");
        $upload_path = 'uploads/' . $pending_container_layer->id;
        $fs = Storage::disk('local');
        $fs->delete($upload_path);
        $pending_container_layer->delete();

        return response('', 200);
    }

    public function upload_status(
        Request $request,
        string $container_ref,
        string $upload_ref
    ){
        $pending_upload = PendingContainerLayer::findOrFail($upload_ref);
        $storage = Storage::drive('local');
        $headers = [
            'Range' => '0-0',
            'Content-Length' => 0,
            'Docker-Upload-UUID' => $pending_upload->id
        ];

        if($storage->exists($pending_upload->rel_upload_path)){
            $headers['Range'] = '0-'.$storage->size($pending_upload->rel_upload_path);
        }

        return response('', 202)->withHeaders($headers);
    }
}
