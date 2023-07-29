<?php

namespace App\Lib\RegistryStorage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Uid\Ulid;

class RegistryStorage {
    public function __construct(
        private Filesystem $fs
    ){

    }

    public function create_upload(): PendingUpload {
        return tap(PendingUpload::make(Ulid::generate()), fn(PendingUpload $upload) => $upload->create());
    }

    public function fetch_upload(string $ulid): ?PendingUpload {
        $upload = PendingUpload::make($ulid);

        if(!$upload->exists()){
            return null;
        }

        return $upload;
    }

    public function create_manifest(string $manifest_body, string $container_path, string $manifest_reference): ContainerManifest {
        $manifest_content_hash = hash('sha256', $manifest_body);

        $manifest_hash_path = ContainerManifest::manifest_path_for($container_path, $manifest_content_hash);
        $manifest_tag_path = ContainerManifest::tag_path_for($container_path, $manifest_reference);

        $this->fs->makeDirectory(dirname($manifest_hash_path));
        $this->fs->makeDirectory(dirname($manifest_tag_path));

        $this->fs->put($manifest_hash_path, $manifest_body);

        if(!str_starts_with($manifest_reference, 'sha256:')) {
            $this->fs->put($manifest_tag_path, $manifest_content_hash);
        }

        return ContainerManifest::make($container_path, $manifest_reference);
    }

    public function fetch_manifest(string $container_path, string $tag): ?ContainerManifest {
        $manifest = ContainerManifest::make($container_path, $tag);
        if($manifest->manifest_hash == null) {
            return null;
        }

        return $manifest;
    }

    public function fetch_container_layer(string $hash): ?ContainerLayer {
        $layer = ContainerLayer::make($hash);
        if(!$layer->exists()) {
            return null;
        }

        return $layer;
    }

    public static function strip_hash_algo(string $hash): string {
        if(str_starts_with($hash, 'sha256:')) {
            return substr($hash, strlen('sha256:'));
        }

        return $hash;
    }
}