<?php

namespace App\Lib\DockerClient;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BadAuthenticationCredentialsException extends DockerClientException
{
    public static function bearer_token_auth($registry, $realm, $username): self {
        return new self("Invalid authentication credentials against realm $realm for registry $registry with username $username");
    }

    public function render(Request $request): Response{
        return response(
            new DockerRegistryErrorBag(
                new DockerRegistryError(DockerRegistryError::ERR_UNAUTHENTICATED, $this->message)
            ),
            401
        );
    }
}
