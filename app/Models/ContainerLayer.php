<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\ContainerLayer
 *
 * @property int $id
 * @property string $docker_hash
 * @property string $container
 * @property string $registry
 * @property int $size
 * @property string|null $temporary_filename
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereDockerHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereRegistry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereTemporaryFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContainerLayer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ContainerLayer extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function manifests(){
        return $this->belongsToMany(ManifestMetadata::class);
    }
}
