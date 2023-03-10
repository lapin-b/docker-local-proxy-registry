<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ProcessesDockerManifests;
use App\Lib\DockerClient\Client;
use App\Models\ManifestMetadata;
use App\Models\ManifestTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ManifestsController extends Controller
{
    use ProcessesDockerManifests;

    public function get_manifest(Request $request, $registry, $container_ref, $manifest_ref){
        $logger = Log::withContext(['container_ref' => $container_ref, 'registry' => $registry, 'manifest' => $manifest_ref]);

        $logger->info('Checking remote registry for existing and updated manifest');
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
            $headers = [
                'Docker-Content-Digest' => $existing_manifest->docker_hash,
                'Content-Type' => $existing_manifest->content_type,
                'Content-Length' => $existing_manifest->size,
                'Docker-Proxy-Cache' => 'HIT',
            ];

            if($request->method() != 'HEAD'){
                $logger->info('Manifest exists in cache and request is not HEAD, serving from S3');
                $redirect = Storage::drive('s3')
                    ->temporaryUrl(
                        "proxy/$registry/$container_ref/manifests/$existing_manifest->docker_hash",
                        now()->addMinutes(5),
                        [
                            "ResponseContentType" => $existing_manifest->content_type
                        ]
                    );
                return Response::redirectTo($redirect, 307, $headers);
            }

            $logger->info('Manifest exists in cache and request is HEAD, sending response');
            return response('', 200, $headers);
        }

        $logger->info('No manifest found, serving from remote and caching');
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

        $db_manifest = $this->createManifestAndLinkedTag(
            $manifest_ref, $container_ref, $registry,
            $content_type, $manifest_size, $docker_hash
        );

        $this->syncLayerRelationships($response_body, $db_manifest);

        return response($request->method() == 'HEAD' ? '' : $response_body, 200, [
            'Content-Type' => $content_type,
            'Docker-Content-Digest' => $docker_hash,
            'Content-Length' => $manifest_size,
            'Docker-Proxy-Cache' => 'MISS'
        ]);
    }
}
