<?php

namespace App\Lib\DockerClient\Authentication;

use App\Lib\DockerClient\BadAuthenticationCredentialsException;
use App\Lib\DockerClient\DockerClientException;
use App\Models\DockerRegistryClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BearerTokenStrategy implements AuthenticationStrategy
{
    private ?string $username;
    private ?string $password;
    private string $registry;
    private string $container;
    private ?string $token = null;

    public function __construct(string $registry, string $container, ?string $username = null, ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->registry = $registry;
        $this->container = $container;
    }

    function inject_authentication(PendingRequest $request): PendingRequest
    {
        return $request;
    }

    function is_valid()
    {
        $existing_record = DockerRegistryClient::where('registry', $this->registry)
            ->where('container', $this->container)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        return $existing_record != null;
    }

    function execute_authentication(Collection $challenge_data)
    {
        $existing_record = DockerRegistryClient::where('registry', $this->registry)
            ->where('container', $this->container)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        if($existing_record != null){
            $this->token = $existing_record->access_token;
            return;
        }

        $realm = $challenge_data->pull('realm');
        if(is_null($realm)){
            throw new DockerClientException("No realm passed for bearer token authentication");
        }

        $challenge_data['scope'] = "repository:$this->container:pull";
        $challenge_string = http_build_query($challenge_data->toArray());
        $pending_request = Http::withOptions([]);

        if($this->username != null){
            $pending_request->withBasicAuth($this->username, $this->password);
        }

        $response = $pending_request->get("$realm?$challenge_string");

        if($response->status() == 401){
            throw BadAuthenticationCredentialsException::bearer_token_auth($this->registry, $realm, $this->username);
        }

        /**
         * @var $token_payload array{token: string, issued_at: ?string, expires_in: ?int}
         */
        $token_payload = $response->json();
        // Inspiration from https://github.com/camallo/dkregistry-rs/blob/37acecb4b8139dd1b1cc83795442f94f90e1ffc5/src/v2/auth.rs#L67.
        // Apparently, token servers can return a 200 and "unauthenticated" as a token. Why ?
        if(empty($token_payload['token']) || $token_payload['token'] == 'authenticated'){
            throw BadAuthenticationCredentialsException::bearer_token_auth($this->registry, $realm, $this->username);
        }

        $issued_at = empty($token_payload['issued_at']) ? now() : Carbon::parse($token_payload['issued_at']);
        $expires_in = empty($token_payload['expires_in']) ? 55 : intval($token_payload['expires_in']) - 5;
        $expires_at = $issued_at->addSeconds($expires_in);
        $this->token = $token_payload['token'];

        DockerRegistryClient::create([
            'registry' => $this->registry,
            'container' => $this->container,
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
            'validity_time' => $expires_in,
            'access_token' => $this->token
        ]);
    }
}
