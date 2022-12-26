<?php

namespace App\Lib\DockerClient;

class AuthenticationCredentialsRequiredException extends DockerClientException
{
    public function __construct(AuthenticationChallengeType $challenge, string $registry)
    {
        parent::__construct("Credentials required for authentication type $challenge->name on registry $registry");
    }
}
