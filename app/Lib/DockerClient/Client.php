<?php

namespace App\Lib\DockerClient;

use App\Lib\DockerClient\Authentication\AnonymousAuthStrategy;
use App\Lib\DockerClient\Authentication\AuthenticationStrategy;
use App\Lib\DockerClient\Authentication\BearerTokenStrategy;
use App\Lib\DockerClient\Authentication\HttpBasicStrategy;
use App\Models\DockerRegistryCredential;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

enum AuthenticationChallengeType {
    case Basic;
    case Bearer;

    public static function from(string $auth_method): self
    {
        return match($auth_method) {
            "Basic" => self::Basic,
            "Bearer" => self::Bearer,
            default => throw new \RuntimeException("Unhandled challenge type $auth_method")
        };
    }
}

class Client
{
    private const SUPPORTED_MIMETYPES = [
        "application/vnd.docker.distribution.manifest.v2+json",
        "application/vnd.docker.distribution.manifest.list.v2+json",
        "application/vnd.docker.image.rootfs.diff.tar.gzip",
        "application/vnd.docker.image.rootfs.foreign.diff.tar.gzip"
    ];

    private ?AuthenticationStrategy $authentication = null;
    private string $registry;
    private string $container;
    private string $base_url;

    public function __construct(string $registry, string $container){
        $this->registry = $registry;
        $this->container = $container;
        $this->base_url = "https://$registry/v2";
    }

    public function authenticate(){
        if(!is_null($this->authentication) && $this->authentication->is_valid()){
            return;
        }

        $base_response = Http::get("$this->base_url/");
        if($base_response->status() == 200){
            $this->authentication = new AnonymousAuthStrategy();
        } else if($base_response->status() != 401){
            throw new UnexpectedStatusCodeException(401, $base_response->status(), "$this->base_url/");
        }

        $challenge = $base_response->header('www-authenticate');
        list($challenge_type, $challenge_info) = $this->_parseAuthenticateChallenge($challenge);
        $registry_credentials = DockerRegistryCredential::where('registry', $this->registry)
            ->limit(1)
            ->first();

        switch($challenge_type){
            case AuthenticationChallengeType::Basic:
                if(is_null($registry_credentials)){
                    throw new AuthenticationCredentialsRequiredException($challenge_type, $this->registry);
                }
                $authentication = new HttpBasicStrategy($registry_credentials->username, $registry_credentials->password);
                break;
            case AuthenticationChallengeType::Bearer:
                $authentication = new BearerTokenStrategy(
                    $this->registry,
                    $this->container,
                    $registry_credentials?->username ?? null,
                    $registry_credentials?->password ?? null
                );
                break;
            default:
                throw new \RuntimeException("Unknown challenge type $challenge_type");
        }

        $authentication->execute_authentication($challenge_info);
        $this->authentication = $authentication;
    }

    public function get_manifest($manifest, bool $head = false): Response {
        $url = "$this->base_url/$this->container/manifests/$manifest";
        $response = $this->authentication->inject_authentication(Http::withOptions([]))
            ->accept(implode(',', self::SUPPORTED_MIMETYPES))
            ->get($url);

        if($response->status() == 404){
            throw RegistryObjectNotFoundException::manifest($this->registry, $this->container, $manifest);
        } else if($response->status() != 200){
            throw new UnexpectedStatusCodeException(200, $response->status(), $url);
        }

        return $response;
    }

    /**
     * @param string $challenge
     * @return array{AuthenticationChallengeType, \Illuminate\Support\Collection}
     */
    private function _parseAuthenticateChallenge(string $challenge) {
        // Valid: Basic realm="...", something="..."
        // Valid: Bearer realm="...", something="..."
        preg_match_all(
            '/((?P<method>[A-Za-z]+)\s)?(?P<key>[A-Za-z]+)\s*=\s*"(?P<value>[^"]+)/',
            $challenge,
            $matches
        );

        $challenge_type = AuthenticationChallengeType::from($matches['method'][0]);
        $challenge_parameters = collect($matches['key'])
            ->zip($matches['value'])
            ->mapWithKeys(fn ($value, $key) => [$value[0] => $value[1]]);

        return [$challenge_type, $challenge_parameters];
    }
}
