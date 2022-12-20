<?php

namespace App\Http\Controllers;

use App\Models\ManifestMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ManifestsController extends Controller
{
    public function upload_manifest(Request $request, string $container_ref, string $manifest_ref) {
        $manifest_content = $request->getContent();
        $file_hash = hash('sha256', $manifest_content);
        $manifest_hash_file = "repository/$container_ref/manifests/sha256:$file_hash";
        $manifest_ref_file = "repository/$container_ref/manifests/$manifest_ref";
        $manifest_directory = dirname($manifest_hash_file);

        // Écrire le manifeste dans le fichier
        $storage = Storage::drive('local');
        $storage->makeDirectory($manifest_directory);
        $storage->put($manifest_hash_file, $manifest_content);
        $file_size = $storage->size($manifest_hash_file);

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
        $storage = Storage::disk('local');

        if($storage->fileMissing($manifest_ref_file)){
            abort(404);
        }

        $metadata = ManifestMetadata::where('manifest_reference', $manifest_ref)
            ->where('container_reference', $container_ref)
            ->firstOrFail();

        return response()
            ->file($storage->path($manifest_ref_file), [
                    'Docker-Content-Digest' => $metadata->docker_hash,
                    'Content-Type' => $metadata->content_type
                ]);
    }
}
