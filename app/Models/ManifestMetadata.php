<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\ManifestMetadata
 *
 * @property int $id
 * @property string $docker_hash
 * @property string $container
 * @property string|null $registry
 * @property string $content_type
 * @property string $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereDockerHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereRegistry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestMetadata whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ManifestMetadata extends Model
{
    use HasFactory;
    protected $table = 'manifest_metadata';
    protected $guarded = ['created_at', 'updated_at', 'id'];
}
