<?php

namespace App\Http\Controllers;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Lib\RegistryStorage\RegistryStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToRetrieveMetadata;

class BlobsController extends Controller
{
    public function __construct(
        private RegistryStorage $storage
    ) {
        
    }

    public function get_blob(Request $request, string $container_ref, string $blob_ref) {
        $layer = $this->storage->fetch_container_layer($blob_ref);
        if($layer == null) {
            return response('', 404);
        }

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Docker-Content-Digest' => 'sha256:' . $layer->hash,
            'Content-Length' => $layer->size(),
        ];

        if($request->isMethod('HEAD')) {
            return response('', 200, $headers);
        }

        return response()->file($layer->absolute_path(), $headers);
    }
}
