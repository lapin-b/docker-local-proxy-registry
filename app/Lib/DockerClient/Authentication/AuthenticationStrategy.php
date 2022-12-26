<?php

namespace App\Lib\DockerClient\Authentication;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

interface AuthenticationStrategy
{
    function inject_authentication(PendingRequest $request): PendingRequest;
    function is_valid();
    function execute_authentication(Collection $challenge_data);
}
