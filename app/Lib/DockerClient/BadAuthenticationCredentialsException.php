<?php

namespace App\Lib\DockerClient;

class BadAuthenticationCredentialsException extends DockerClientException
{
    public static function bearer_token_auth($registry, $realm, $username): self {
        return new self("Invalid authentication credentials against realm $realm for registry $registry with username $username");
    }
}
