<?php

namespace App\Lib\DockerClient;

enum ObjectType {
    case Manifest;
    case Blob;
}

class RegistryObjectNotFoundException extends DockerClientException
{
    private ObjectType $registry_object;

    public static function manifest($registry, $container, $manifest){
        $ex = new self("Manifest $manifest for container $container on $registry not found");
        $ex->registry_object = ObjectType::Manifest;
        return $ex;
    }
}
