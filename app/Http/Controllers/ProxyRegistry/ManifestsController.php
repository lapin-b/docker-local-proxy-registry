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
        $manifest_head = $client->get_manifest($manifest_ref, true);

        // Vérifier que l'on dispose du manifeste. Sur le DockerHub, les HEAD ne sont pas comptés
        // alors que les GET le sont.
        //
        // La base de données fait office de source de vérité parce que c'est plus facile d'y accéder
        // et ne fait pas de frais, si le bucket S3 est facturé à l'usage
        $existing_manifest = ManifestMetadata::where('docker_hash', $manifest_head->header('Docker-Content-Digest'))
            ->where('registry', $registry)
            ->where('container', $container_ref)
            ->first();

        if(!is_null($existing_manifest)){
            $code = 200;
            $headers = [
                'Docker-Content-Digest' => $existing_manifest->docker_hash,
                'Content-Type' => $existing_manifest->content_type,
                'Content-Length' => $existing_manifest->size,
                'Docker-Proxy-Cache' => 'HIT',
            ];

            if($request->method() != 'HEAD'){
                $code = 307;
                $headers['Location'] = Storage::drive('s3')
                    ->temporaryUrl(
                        "proxy/$registry/$container_ref/manifests/$existing_manifest->docker_hash",
                        now()->addMinutes(5),
                        [
                            "ResponseContentType" => $existing_manifest->content_type
                        ]
                    );
            }

            return response('', $code, $headers);
        }

        $proxied_manifest = $client->get_manifest($manifest_ref);

        $docker_hash = $proxied_manifest->header('Docker-Content-Digest');
        $content_type = $proxied_manifest->header('Content-Type');
        $manifest_size = $proxied_manifest->header('Content-Length');

        $response_body = $proxied_manifest->body();

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

        return response($request->method() == 'HEAD' ? '' : $response_body, 200, [
            'Content-Type' => $content_type,
            'Docker-Content-Digest' => $docker_hash,
            'Content-Length' => $manifest_size,
            'Docker-Proxy-Cache' => 'MISS'
        ]);
    }
}
