<?php

namespace App\Lib\DockerClient;

class UnexpectedStatusCodeException extends DockerClientException
{
    public static function create($expected_code, $actual, $uri){
        return new self("Expected HTTP status $expected_code, got $actual while querying $uri");
    }
}
