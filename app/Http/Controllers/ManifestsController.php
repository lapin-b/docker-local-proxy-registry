<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ProcessesDockerManifests;
use App\Lib\RegistryStorage\RegistryStorage;
use Illuminate\Http\Request;

class ManifestsController extends Controller
{
    public function __construct(
        private RegistryStorage $registry
    ) {

    }

    public function upload_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_content = $request->getContent();
        $manifest = $this->registry->create_manifest($manifest_content, $container_ref, $manifest_ref);

        return response('', 201)
            ->header('Location', route('manifests.get', compact('manifest_ref', 'container_ref')))
            ->header('Docker-Content-Digest', 'sha256:' . $manifest->manifest_hash);
    }

    public function get_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest = $this->registry->fetch_manifest($container_ref, $manifest_ref);
        if($manifest == null) {
            return response('', 404);
        }

        $manifest_content = $manifest->get();
        $decoded_manifest = json_decode($manifest_content, true);
        $manifest_mime_type = $decoded_manifest['mediaType'];

        return response($manifest_content, 200, [
            'Docker-Content-Digest' => 'sha256:' . $manifest->manifest_hash,
            'Content-Type' => $manifest_mime_type,
            'Content-Length' => $manifest->size()
        ]);
    }
}
