<?php

namespace App\Http\Middleware;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Closure;
use Illuminate\Http\Request;

class ValidateContainerLayerComponents
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $container_reference = $request->route('container_ref');
        $manifest_reference = $request->route('manifest_ref');
        $blob_reference = $request->route('blob_ref');

        if($container_reference != null && str_contains($container_reference, '..')) {
            return response(new DockerRegistryErrorBag(DockerRegistryError::invalid_namespace($container_reference)), 400);
        }

        if($manifest_reference != null && str_contains($manifest_reference, '..')){
            return response(new DockerRegistryErrorBag(DockerRegistryError::invalid_tag($manifest_reference)), 400);
        }

        if($blob_reference != null && str_contains($blob_reference, '..')){
            return response(new DockerRegistryErrorBag(DockerRegistryError::invalid_tag($blob_reference)), 400);
        }

        return $next($request);
    }
}
