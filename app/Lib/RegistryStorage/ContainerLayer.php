<?php

namespace App\Lib\RegistryStorage;

class ContainerLayer {
    public const STORAGE_FOLDER = 'layers';

    public static function storage_directory(string $hash) {
        return self::STORAGE_FOLDER . '/' . substr($hash, 0, 2);
    }

    public static function storage_path(string $hash) {
        return self::storage_directory($hash) . '/' . $hash;
    }
}