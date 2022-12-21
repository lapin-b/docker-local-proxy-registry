<?php

namespace App\Lib;

use JsonSerializable;

class DockerRegistryError implements JsonSerializable
{
    private const CODE = 'code';
    private const MSG = 'message';
    private const DETAILS = 'detail';

    const ERR_INVALID_TAG = 'TAG_INVALID';
    const ERR_INVALID_NAME = 'NAME_INVALID';

    public string $code;
    public string $message;
    public ?string $detail;

    public function __construct(string $code, string $message, ?string $detail = null){
        $this->code = $code;
        $this->message = $message;
        $this->detail = $detail;
    }

    public static function invalid_tag(string $tag): self {
        return new self(
            self::ERR_INVALID_TAG,
            "Container tag [$tag] is invalid",
        );
    }

    public static function invalid_namespace(string $namespace): self {
        return new self(
            self::ERR_INVALID_NAME,
            "Container name [$namespace] is invalid"
        );
    }

    public function jsonSerialize(): array
    {
        return [
            self::CODE => $this->code,
            self::MSG => $this->message,
            self::DETAILS => $this->detail
        ];
    }
}
