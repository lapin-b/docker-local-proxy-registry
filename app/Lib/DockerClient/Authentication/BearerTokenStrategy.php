<?php

namespace App\Lib\DockerClient\Authentication;

use App\Lib\DockerClient\BadAuthenticationCredentialsException;
use App\Lib\DockerClient\DockerClientException;
use App\Models\DockerRegistryClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\Logger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BearerTokenStrategy implements AuthenticationStrategy
{
    private ?string $username;
    private ?string $password;
    private string $registry;
    private string $container;
    private ?string $token = null;

    private string $cache_key;

    public function __construct(string $registry, string $container, ?string $username = null, ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->registry = $registry;
        $this->container = $container;

        $this->cache_key = "bearer-" . $registry . '.' . $container;
    }

    function inject_authentication(PendingRequest $request): PendingRequest
    {
        return $request->withToken($this->token);
    }

    function is_valid()
    {
        return Cache::has($this->cache_key);
    }

    function execute_authentication(Collection $challenge_data)
    {
        $logger = Log::withContext([
            'registry' => $this->registry,
            'realm' => $challenge_data->get('realm'),
            'container_ref' => $this->container
        ]);

        if($this->_reuse_existing_token()){
            $logger->info('Found valid token in non-lock environment');
            return;
        }

        $lock = Cache::lock("lock-$this->registry.$this->container");
        $lock->get(function() use ($logger, $challenge_data){
            $this->_execute_authentication($challenge_data, $logger);
        });
    }

    private function _execute_authentication(Collection $challenge_data, Logger $logger){
        if($this->_reuse_existing_token()){
            $logger->info('Found valid token in lock environment');
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

        $logger->info('Attempting authentication with username ' . $this->username ?? '<no username>');
        $response = $pending_request->get("$realm?$challenge_string");

        if($response->status() == 401){
            $logger->error('Received HTTP 401, invalid credentials');
            throw BadAuthenticationCredentialsException::bearer_token_auth($this->registry, $realm, $this->username);
        }

        /**
         * @var $token_payload array{token: string, issued_at: ?string, expires_in: ?int}
         */
        $token_payload = $response->json();
        // Inspiration from https://github.com/camallo/dkregistry-rs/blob/37acecb4b8139dd1b1cc83795442f94f90e1ffc5/src/v2/auth.rs#L67.
        // Apparently, token servers can return a 200 and "unauthenticated" as a token. Why ?
        if(empty($token_payload['token']) || $token_payload['token'] == 'unauthenticated'){
            $logger->error('Registry returned 200 with unauthenticated token. Considering invalid credentials');
            throw BadAuthenticationCredentialsException::bearer_token_auth($this->registry, $realm, $this->username);
        }

        $issued_at = empty($token_payload['issued_at']) ? now() : Carbon::parse($token_payload['issued_at']);
        $expires_in = empty($token_payload['expires_in']) ? 55 : intval($token_payload['expires_in']) - 5;
        $expires_at = $issued_at->addSeconds($expires_in);
        $this->token = $token_payload['token'];

        $logger->info('Successfully authenticated to the registry');
        Cache::put($this->cache_key, $this->token, $expires_at);
    }

    private function _reuse_existing_token(){
        $existing_token = Cache::get($this->cache_key);

        if($existing_token != null){
            $this->token = $existing_token;
            return true;
        }

        return false;
    }
}
