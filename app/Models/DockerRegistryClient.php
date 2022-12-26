<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DockerRegistryClient
 *
 * @property int $id
 * @property string $registry
 * @property string $container
 * @property string $issued_at
 * @property string $expires_at
 * @property int $validity_time
 * @property string $access_token
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient query()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereRegistry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryClient whereValidityTime($value)
 * @mixin \Eloquent
 */
class DockerRegistryClient extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = ['id'];
}
