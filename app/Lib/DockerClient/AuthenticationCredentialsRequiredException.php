<?php

namespace App\Lib\DockerClient;

use App\Lib\DockerRegistryError;
use App\Lib\DockerRegistryErrorBag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthenticationCredentialsRequiredException extends DockerClientException
{
    public static function create(AuthenticationChallengeType $challenge, string $registry): self {
        return new self("Credentials required for authentication type $challenge->name on registry $registry");
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
