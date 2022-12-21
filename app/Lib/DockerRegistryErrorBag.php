<?php

namespace App\Lib;

use JsonSerializable;

class DockerRegistryErrorBag implements JsonSerializable
{
    /** @var DockerRegistryError[] */
    private array $errors;

    /**
     * @param DockerRegistryError|DockerRegistryError[]|null $errors
     */
    public function __construct(array|DockerRegistryError|null $errors){
        if(is_array($errors)){
            $this->errors = $errors;
        } else if(is_null($errors)){
            $this->errors = [];
        } else {
            $this->errors = [$errors];
        }
    }


    public function jsonSerialize()
    {
        return ['errors' => $this->errors];
    }
}
