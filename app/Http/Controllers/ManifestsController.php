<?php

namespace App\Http\Controllers;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Models\ManifestMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Response;

class ManifestsController extends Controller
{
    public function upload_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_content = $request->getContent();
        $file_hash = hash('sha256', $manifest_content);
        $manifest_hash_file = "repository/$container_ref/manifests/sha256:$file_hash";
        $manifest_ref_file = "repository/$container_ref/manifests/$manifest_ref";

        // Écrire le manifeste dans le fichier
        $storage = Storage::drive('s3');
        $storage->put($manifest_hash_file, $manifest_content);
        $file_size = strlen($manifest_content);

        $insert_values = [
            'docker_hash' => "sha256:$file_hash",
            'content_type' => $request->header('Content-Type'),
            'filesize' => $file_size
        ];

        if($manifest_hash_file != $manifest_ref_file){
            // We don't copy if the manifest reference is the same as its hash file
            $storage->copy($manifest_hash_file, $manifest_ref_file);

            ManifestMetadata::updateOrInsert([
                'container_reference' => $container_ref,
                'manifest_reference' => $manifest_ref,
            ], $insert_values);
        }

        ManifestMetadata::updateOrInsert([
            'container_reference' => $container_ref,
            'manifest_reference' => "sha256:$file_hash",
        ], $insert_values);

        return response('', 200)
            ->header('Location', route('manifests.get', compact('manifest_ref', 'container_ref')))
            ->header('Docker-Content-Digest', "sha256:$file_hash");
    }

    public function get_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_ref_file = "repository/$container_ref/manifests/$manifest_ref";
        $storage = Storage::disk('s3');

        if($storage->fileMissing($manifest_ref_file)){
            return response(new DockerRegistryErrorBag(DockerRegistryError::unknown_manifest($manifest_ref, $container_ref)), 404);
        }

        $metadata = ManifestMetadata::where('manifest_reference', $manifest_ref)
            ->where('container_reference', $container_ref)
            ->firstOrFail();

        return response($storage->get($manifest_ref_file))
            ->header('Docker-Content-Digest', $metadata->docker_hash)
            ->header('Content-Length', $metadata->filesize)
            ->header('Content-Type', $metadata->content_type);
    }
}
