<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PendingContainerLayer
 *
 * @property string $id
 * @property string $container_reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer query()
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer whereContainerReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PendingContainerLayer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PendingContainerLayer extends Model
{
    use HasFactory;
    public $incrementing = false;
}
