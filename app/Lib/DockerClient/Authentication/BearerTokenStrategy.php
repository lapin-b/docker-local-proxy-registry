<?php

namespace App\Lib\DockerClient\Authentication;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class BearerTokenStrategy implements AuthenticationStrategy
{
    private ?string $username;
    private ?string $password;

    public function __construct(?string $username, ?string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    function inject_authentication(PendingRequest $request): PendingRequest
    {
        return $request;
    }

    function is_valid()
    {
        // TODO: Implement is_valid() method.
        return true;
    }

    function execute_authentication(Collection $challenge_data)
    {
        // TODO: Implement execute_authentication() method.
    }
}
