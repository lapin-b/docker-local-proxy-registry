<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DockerRegistryCredential
 *
 * @property int $id
 * @property string $registry
 * @property string $username
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential query()
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential whereRegistry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DockerRegistryCredential whereUsername($value)
 * @mixin \Eloquent
 */
class DockerRegistryCredential extends Model
{
    use HasFactory;
}
