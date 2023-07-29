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

    public static function strip_hash_algo(string $hash): string {
        if(str_starts_with($hash, 'sha256:')) {
            return substr($hash, strlen('sha256:'));
        }

        return $hash;
    }
}