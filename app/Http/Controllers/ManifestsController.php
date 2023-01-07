<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ProcessesDockerManifests;
use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Models\ManifestMetadata;
use App\Models\ManifestTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Response;

class ManifestsController extends Controller
{
    use ProcessesDockerManifests;

    public function upload_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_content = $request->getContent();

        $docker_hash = 'sha256:' . hash('sha256', $manifest_content);
        $file_size = strlen($manifest_content);
        $manifest_hash_path = "repository/$container_ref/manifests/$docker_hash";

        // Write the manifest in the file in the storaged
        $storage = Storage::drive('s3');
        $storage->put($manifest_hash_path, $manifest_content);

        // Save the manifest reference into the database. The manifest will be referred by its hash and,
        // if the original manifest reference is not a hash, we simulate making a symbolic link. Finally,
        // we do the post-processing required to keep the state of the database in sync with the remote
        // storage.

        $db_manifest = $this->createManifestAndLinkedTag(
            $manifest_ref, $container_ref, null,
            $request->header('Content-Type'), $file_size,
            $docker_hash
        );

        $this->syncLayerRelationships($manifest_content, $db_manifest);

        return response('', 201)
            ->header('Location', route('manifests.get', compact('manifest_ref', 'container_ref')))
            ->header('Docker-Content-Digest', $docker_hash);
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
