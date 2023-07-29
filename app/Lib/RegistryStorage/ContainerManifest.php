<?php

namespace App\Lib\RegistryStorage;

use Illuminate\Contracts\Filesystem\Filesystem;

class ContainerManifest {
    public const STORAGE_PATH = 'registry';

    private Filesystem $fs;
    public string $container_path;
    public ?string $manifest_tag = null;
    public ?string $manifest_hash = null;

    public function __construct(){
        $this->fs = resolve('filesystem.disk');
    }

    public function get(): string {
        return $this->fs->get(self::manifest_path_for($this->container_path, $this->manifest_hash));
    }

    public static function make(string $container_name, string $manifest_reference) {
        $manifest = new self;
        $manifest->container_path = $container_name;
        $manifest->manifest_hash = self::resolve_container_tag($container_name, $manifest_reference);

        if(!str_starts_with('sha256:', $manifest_reference)){
            $manifest->manifest_tag = $manifest_reference;
        }

        return $manifest;
    }

    private static function resolve_container_tag(string $container_path, string $tag_or_hash): ?string {
        $fs = resolve('filesystem.disk');
        $tag_file = self::tag_path_for($container_path, $tag_or_hash);

        if(!str_starts_with($tag_or_hash, 'sha256:') && $fs->exists($tag_file)) {
            return trim($fs->get($tag_file));
        } else if(str_starts_with($tag_or_hash, 'sha256:')) {
            return RegistryStorage::strip_hash_algo($tag_or_hash);
        } else {
            return null;
        }
    }

    public static function manifest_path_for(string $container_ref, string $manifest): string {
        return self::STORAGE_PATH . '/' . $container_ref . '/manifests/' . $manifest;
    }

    public static function tag_path_for(string $container_ref, string $tag): string {
        return self::STORAGE_PATH . '/' . $container_ref . '/tags/' . $tag;
    }
}