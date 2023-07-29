<?php

namespace App\Lib\RegistryStorage;

use Illuminate\Filesystem\FilesystemAdapter;

class ContainerLayer {
    public const STORAGE_FOLDER = 'layers';

    private FilesystemAdapter $fs;
    public string $container_name;
    public string $hash;

    public function __construct(){
        $this->fs = resolve('filesystem.disk');
    }

    public function size(): int {
        return $this->fs->fileSize(self::storage_path($this->hash));
    }

    public function absolute_path(): string {
        return $this->fs->path(self::storage_path($this->hash));
    }

    public function exists(): bool {
        return $this->fs->exists(self::storage_path($this->hash));
    }

    public static function make(string $blob): self {
        $layer = new self;
        $layer->hash = RegistryStorage::strip_hash_algo($blob);

        return $layer;
    }

    public static function storage_directory(string $hash) {
        return self::STORAGE_FOLDER . '/' . substr($hash, 0, 2);
    }

    public static function storage_path(string $hash) {
        return self::storage_directory($hash) . '/' . $hash;
    }
}