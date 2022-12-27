<?php

namespace App\Lib\DockerClient;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use App\Models\DockerRegistryClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

enum ObjectType {
    case Manifest;
    case Blob;
}

class RegistryObjectNotFoundException extends DockerClientException
{
    public ObjectType $registry_object;

    public static function manifest($registry, $container, $manifest){
        $ex = new self("Manifest $manifest for container $registry/$container not found");
        $ex->registry_object = ObjectType::Manifest;
        return $ex;
    }

    public static function blob(string $registry, string $container, string $blob)
    {
        $ex = new self("Blob $blob for container $registry/$container not found");
        $ex->registry_object = ObjectType::Blob;
        return $ex;
    }

    /**
     * Report the exception (called by Laravel)
     * @return boolean
     */
    public function report(): bool
    {
        return false;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function render(Request $request): Response{
        return response(
            new DockerRegistryErrorBag(
                new DockerRegistryError(
                    $this->registry_object == ObjectType::Blob
                        ? DockerRegistryError::ERR_UNKNOWN_BLOB
                        : DockerRegistryError::ERR_UNKNOWN_NAME,
                    $this->message
                )
            ),
            404
        );
    }
}
