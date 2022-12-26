<?php

namespace App\Lib\DockerClient\Authentication;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class HttpBasicStrategy implements AuthenticationStrategy
{
    private string $username;
    private ?string $password;

    public function __construct(string $username, ?string $password) {
        $this->username = $username;
        $this->password = $password;
    }

    function inject_authentication(PendingRequest $request): PendingRequest
    {
        return $request->withBasicAuth($this->username, $this->password);
    }

    function is_valid()
    {
        return true;
    }

    function execute_authentication(Collection $challenge_data)
    {

    }
}
