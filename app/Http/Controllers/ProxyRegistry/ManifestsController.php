<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Lib\DockerClient\Client;
use App\Models\ManifestMetadata;
use App\Models\ManifestTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManifestsController extends Controller
{
    public function get_manifest(Request $request, $registry, $container_ref, $manifest_ref){
        $client = new Client($registry, $container_ref);
        $client->authenticate();
        $response = $client->get_manifest($manifest_ref, $request->method() == 'HEAD');

        $docker_hash = $response->header('Docker-Content-Digest');
        $content_type = $response->header('Content-Type');
        $manifest_size = $response->header('Content-Length');

        if($request->method() != 'HEAD') {
            $response_body = $response->body();

            $storage = Storage::disk('s3');
            $storage->write(
                "proxy/$registry/$container_ref/manifests/$docker_hash",
                $response_body
            );

            $db_manifest = ManifestMetadata::updateOrCreate(
                [
                    'docker_hash' => $docker_hash,
                    'container' => $container_ref,
                    'registry' => $registry,
                ],
                [
                    'content_type' => $content_type,
                    'size' => $manifest_size
                ]
            );

            if (!str_starts_with($manifest_ref, 'sha256:')) {
                ManifestTag::updateOrInsert(
                    [
                        'container' => $container_ref,
                        'tag' => $manifest_ref,
                        'registry' => $registry,
                    ],
                    [
                        'manifest_metadata_id' => $db_manifest->id
                    ]
                );
            }
        }

        return response($response_body ?? '', 200, [
            'Content-Type' => $content_type,
            'Docker-Content-Digest' => $docker_hash,
            'Content-Length' => $manifest_size
        ]);
    }
}
