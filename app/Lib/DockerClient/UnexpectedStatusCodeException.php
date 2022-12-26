<?php

namespace App\Lib\DockerClient;

class UnexpectedStatusCodeException extends DockerClientException
{
    public $expected;
    public $actual;
    public $uri;

    public function __construct($expected, $actual, $uri){
        $this->expected = $expected;
        $this->actual = $actual;
        $this->uri = $uri;

        return parent::__construct(
            "Expected HTTP status $expected, got $actual while querying $uri"
        );
    }
}
