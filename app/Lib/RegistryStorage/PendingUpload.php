<?php

namespace App\Lib\RegistryStorage;

use Illuminate\Filesystem\FilesystemManager;

class PendingUpload {
    public const STORAGE_FOLDER = 'uploads';

    public function __construct(
        private FilesystemManager $fs,
        public readonly string $ulid
    ) {

    }

    public function create() {
        $this->fs->makeDirectory(self::STORAGE_FOLDER);
        $this->fs->put(self::storage_path($this->ulid));
    }

    /**
     * @param resource $body
     * @return void
     */
    public function append($body){
        $upload_fh = fopen(self::storage_path($this->ulid), 'a');
        stream_copy_to_stream($body, $upload_fh);
        fclose($upload_fh);
    }

    public function size(): int {
        return $this->fs->size(self::storage_path($this->ulid));
    }

    public function move_upload(string $finalized_hash) {
        $finalized_hash = RegistryStorage::strip_hash_algo($finalized_hash);

        $this->fs->makeDirectory(ContainerLayer::storage_directory($finalized_hash));
        $this->fs->move(self::storage_path($this->ulid), ContainerLayer::storage_path($finalized_hash));
    }

    public function delete() {
        $this->fs->delete(self::storage_path($this->ulid));
    }

    public static function storage_path(string $ulid): string {
        return self::STORAGE_FOLDER . '/' . $ulid;
    }
}