<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ProcessesDockerManifests;
use App\Lib\DockerClient\Client;
use App\Lib\RegistryStorage\RegistryStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManifestsController extends Controller
{
    use ProcessesDockerManifests;

    public function __construct(
        private RegistryStorage $storage,
    ) {

    }

    public function get_manifest(Request $request, $registry, $container_ref, $manifest_ref){
        $logger = Log::withContext(['container_ref' => $container_ref, 'registry' => $registry, 'manifest' => $manifest_ref]);

        $logger->info('Checking remote registry for existing and updated manifest');
        $client = new Client($registry, $container_ref);
        $client->authenticate();

        $manifest_head_response = $client->get_manifest($manifest_ref, true);
        $existing_manifest = $this->storage->fetch_manifest("proxy/$registry/$container_ref", $manifest_ref);

        if(
            !is_null($existing_manifest) 
            && $existing_manifest->manifest_hash == RegistryStorage::strip_hash_algo($manifest_head_response->header('Docker-Content-Digest'))
        ){
            $headers = [
                'Docker-Content-Digest' => 'sha256:' . $existing_manifest->manifest_hash,
                'Content-Type' => $existing_manifest->mimeType(),
                'Content-Length' => $existing_manifest->size(),
                'Docker-Proxy-Cache' => 'HIT',
            ];

            return response($request->isMethod('HEAD') ? '' : $existing_manifest->get(), 200, $headers);
        }

        $logger->info('No manifest found, serving from remote and caching');
        $proxied_manifest = $client->get_manifest($manifest_ref);
        $response_body = $proxied_manifest->body();
        $manifest = $this->storage->create_manifest($response_body, "proxy/$registry/$container_ref", $manifest_ref);

        return response($request->method() == 'HEAD' ? '' : $response_body, 200, [
            'Content-Type' => $manifest->mimeType(),
            'Docker-Content-Digest' => 'sha256:' . $manifest->manifest_hash,
            'Content-Length' => $manifest->size(),
            'Docker-Proxy-Cache' => 'MISS'
        ]);
    }
}
