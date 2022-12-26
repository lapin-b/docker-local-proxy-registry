<?php

namespace App\Http\Controllers\ProxyRegistry;

use App\Http\Controllers\Controller;
use App\Lib\DockerClient\Client;
use Illuminate\Http\Request;

class ManifestsController extends Controller
{
    public function get_manifest($registry, $container_ref, $manifest_ref){
        $client = new Client($registry, $container_ref);
        $client->authenticate();

        abort(501);
    }
}
