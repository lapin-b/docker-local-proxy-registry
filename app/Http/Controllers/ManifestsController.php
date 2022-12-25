<?php

namespace App\Http\Controllers;

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
    public function upload_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_content = $request->getContent();
        $file_hash = hash('sha256', $manifest_content);
        $file_size = strlen($manifest_content);
        $manifest_hash_file = "repository/$container_ref/manifests/sha256:$file_hash";

        // Écrire le manifeste dans le fichier
        $storage = Storage::drive('s3');
        $storage->put($manifest_hash_file, $manifest_content);

        // Écrire le manifeste nouvellement créé dans la base de données
        $hash_manifest_metadata = ManifestMetadata::updateOrCreate(
            [
                'docker_hash' => "sha256:$file_hash",
                'container' => $container_ref,
                'registry' => null,
            ],
            [
                'content_type' => $request->header('Content-Type'),
                'size' => $file_size,
            ]
        );

        if(!str_starts_with($manifest_ref, 'sha256:')){
            ManifestTag::updateOrInsert([
                'tag' => $manifest_ref,
                'container' => $container_ref,
                'registry' => null,
            ], [
                'manifest_metadata_id' => $hash_manifest_metadata->id
            ]);
        }

        return response('', 200)
            ->header('Location', route('manifests.get', compact('manifest_ref', 'container_ref')))
            ->header('Docker-Content-Digest', "sha256:$file_hash");
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

        $manifest_ref_file = "repository/$container_ref/manifests/$metadata->docker_hash";
        $storage = Storage::disk('s3');

        if($storage->fileMissing($manifest_ref_file)){
            return response(new DockerRegistryErrorBag(DockerRegistryError::unknown_manifest($manifest_ref, $container_ref)), 404);
        }

        return response($storage->get($manifest_ref_file))
            ->header('Docker-Content-Digest', $metadata->docker_hash)
            ->header('Content-Length', $metadata->filesize)
            ->header('Content-Type', $metadata->content_type);
    }
}
