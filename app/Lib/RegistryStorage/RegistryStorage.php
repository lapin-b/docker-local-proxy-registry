<?php

namespace App\Lib\RegistryStorage;

use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\Uid\Ulid;

class RegistryStorage {
    public function __construct(
        private FilesystemManager $fs
    ){
        
    }

    public function create_upload(): PendingUpload {
        $ulid = Ulid::generate();
        $upload = new PendingUpload($this->fs, $ulid);
        $upload->create();

        return $upload;
    }

    public function fetch_upload(string $ulid): ?PendingUpload {
        if(!$this->fs->exists(PendingUpload::storage_path($ulid))) {
            return null;
        }

        return new PendingUpload($this->fs, $ulid);
    }

    public static function strip_hash_algo(string $hash): string {
        if(str_starts_with($hash, 'sha256:')) {
            return substr($hash, strlen('sha256:'));
        }

        return $hash;
    }
}