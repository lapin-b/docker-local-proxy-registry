<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ProcessesDockerManifests;
use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Lib\RegistryStorage\RegistryStorage;
use App\Models\ManifestMetadata;
use App\Models\ManifestTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Response;

class ManifestsController extends Controller
{
    use ProcessesDockerManifests;

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
        if(!str_starts_with($manifest_ref, 'sha256:')){
            $tag = ManifestTag::where('container', $container_ref)
                ->where('tag', $manifest_ref)
                ->where('registry', null)
                ->firstOrFail();

            $metadata = $tag->manifest_metadata;
        } else {
            $metadata = ManifestMetadata::where('docker_hash', $manifest_ref)
                ->where('container', $container_ref)
                ->where('registry', null)
                ->firstOrFail();
        }

        $manifest_ref_file = "repository/$metadata->container/manifests/$metadata->docker_hash";
        $storage = Storage::disk('s3');

        if($storage->fileMissing($manifest_ref_file)){
            return response(new DockerRegistryErrorBag(DockerRegistryError::unknown_manifest($manifest_ref, $container_ref)), 404);
        }

        return response()->redirectTo(
            $storage->temporaryUrl(
                $manifest_ref_file,
                now()->addMinutes(5),
                [
                    'ResponseContentType' => $metadata->content_type
                ],
            ),
            307,
            [
                'Docker-Content-Digest' => $metadata->docker_hash
            ]
        );
    }
}
