<?php

namespace App\Lib\DockerClient\Authentication;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class AnonymousAuthStrategy implements AuthenticationStrategy
{

    function inject_authentication(PendingRequest $request): PendingRequest
    {
        return $request;
    }

    function is_valid()
    {
        return true;
    }

    function execute_authentication(Collection $challenge_data)
    {
        // no-op
    }
}
