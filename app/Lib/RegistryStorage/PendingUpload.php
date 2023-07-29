<?php

namespace App\Lib\RegistryStorage;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PendingUpload implements UrlRoutable {
    public const STORAGE_FOLDER = 'uploads';

    private Filesystem $fs;
    public string $ulid;

    public function __construct() {
        $this->fs = resolve('filesystem.disk');
    }

    public function create(): void {
        $this->fs->makeDirectory(self::STORAGE_FOLDER);
        $this->fs->put(self::storage_path($this->ulid), '');
    }

    /**
     * @param resource $body
     * @return void
     */
    public function append($body): void {
        $upload_fh = fopen($this->fs->path(self::storage_path($this->ulid)), 'a');
        stream_copy_to_stream($body, $upload_fh);
        fclose($upload_fh);
    }

    public function size(): int {
        return $this->fs->size(self::storage_path($this->ulid));
    }

    public function move_upload(string $finalized_hash): void {
        $finalized_hash = RegistryStorage::strip_hash_algo($finalized_hash);

        $this->fs->makeDirectory(ContainerLayer::storage_directory($finalized_hash));
        $this->fs->move(self::storage_path($this->ulid), ContainerLayer::storage_path($finalized_hash));
    }

    public function delete(): void {
        $this->fs->delete(self::storage_path($this->ulid));
    }

    public function exists(): bool {
        return $this->fs->exists(self::storage_path($this->ulid));
    }

    public static function storage_path(string $ulid): string {
        return self::STORAGE_FOLDER . '/' . $ulid;
    }

    public static function make(string $ulid): self {
        return tap(new self, fn(self $instance) => $instance->ulid = $ulid);
    }

    public function getRouteKey() { 
        return $this->ulid;
    }

    public function getRouteKeyName() {
        return 'upload';
    }

    public function resolveRouteBinding($value, $field = null) { 
        $upload = self::make($value);

        if(!$upload->exists()) {
            throw new ModelNotFoundException('Upload ID ' . $value . ' not found');
        }

        return $upload;
    }

    public function resolveChildRouteBinding($childType, $value, $field) { 
        throw new Exception(self::class . ' does not have child models');
    }
}