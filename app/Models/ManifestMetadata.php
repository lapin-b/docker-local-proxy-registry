<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ManifestMetadata
 *
 * @property int $id
 * @property string $docker_hash
 * @property string $content_type
 * @property int $filesize
 * @property int $is_proxy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereDockerHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereFilesize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereIsProxy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $proxied_registry
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereProxiedRegistry($value)
 * @property string $manifest_reference
 * @property string $container_reference
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereContainerReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereManifestReference($value)
 */
class ManifestMetadata extends Model
{
    use HasFactory;
    protected $guarded = ['created_at', 'updated_at', 'id'];
}
