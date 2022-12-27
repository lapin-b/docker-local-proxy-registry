<?php

namespace App\Lib\DockerClient;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class DockerClientException extends RuntimeException
{
    public function render(Request $request): Response{
        return response(
            new DockerRegistryErrorBag(
                new DockerRegistryError(DockerRegistryError::ERR_UNKNOWN, $this->message)
            ),
            500
        );
    }
}
