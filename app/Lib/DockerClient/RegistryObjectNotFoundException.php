<?php

namespace App\Lib\DockerClient;

enum ObjectType {
    case Manifest;
    case Blob;
}

class RegistryObjectNotFoundException extends DockerClientException
{
    public ObjectType $registry_object;

    public static function manifest($registry, $container, $manifest){
        $ex = new self("Manifest $manifest for container $container on $registry not found");
        $ex->registry_object = ObjectType::Manifest;
        return $ex;
    }

    public static function blob(string $registry, string $container, string $blob)
    {
        $ex = new self("Blob $blob for container $container on $registry not found");
        $ex->registry_object = ObjectType::Blob;
        return $ex;
    }
}
