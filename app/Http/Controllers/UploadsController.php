<?php

namespace App\Http\Controllers;

use App\Lib\RegistryStorage\PendingUpload;
use App\Lib\RegistryStorage\RegistryStorage;
use Illuminate\Http\Request;

class UploadsController extends Controller
{
    public function __construct(
        private RegistryStorage $storage
    ) {

    }

    public function initiateUpload(Request $request, string $container_ref){
        $upload = $this->storage->create_upload();

        return response('', 202)
            ->header(
                'Location',
                route('blobs.process_upload', ['container_ref' => $container_ref, 'upload' => $upload])
            )
            ->header('Content-Length', 0)
            ->header('Range', '0-0')
            ->header('Docker-Upload-UUID', $upload->ulid);
    }

    public function process_partial_update(
        Request $request,
        string $container_ref,
        PendingUpload $upload,
    ){
        $body = $request->getContent(true);
        $upload->append($body);

        if($request->method() == 'PUT') {
            $docker_hash = $request->query('digest');
            $upload->move_upload($docker_hash);

            return response('', 201)
                ->header(
                    'Location', 
                    route('blobs.get', ['container_ref' => $container_ref, 'blob_ref' => $docker_hash])
                )
                ->header('Content-Length', 0)
                ->header('Docker-Content-Digest', $docker_hash);
        }

        return response('', 202)
            ->header(
                'Location',
                route('blobs.process_upload', ['container_ref' => $container_ref, 'upload' => $upload])
            )
            ->header('Range', "0-" . $upload->size() + ftell($body))
            ->header('Content-Length', 0)
            ->header('Docker-Upload-UUID', $upload->ulid);
    }

    public function cancel_upload(
        Request $request,
        string $container_ref,
        string $upload_ref
    ){
        $this->storage->fetch_upload($upload_ref)?->delete();
        return response('', 200);
    }

    public function upload_status(
        Request $request,
        string $container_ref,
        PendingUpload $upload
    ){
        $headers = [
            'Range' => '0-' . $upload->size(),
            'Content-Length' => 0,
            'Docker-Upload-UUID' => $upload->ulid
        ];

        return response('', 204)->withHeaders($headers);
    }
}
