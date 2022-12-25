<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ManifestTag
 *
 * @property int $id
 * @property string $container
 * @property string|null $registry
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $manifest_metadata_id
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereManifestMetadataId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereRegistry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $tag
 * @method static \Illuminate\Database\Eloquent\Builder|ManifestTag whereTag($value)
 * @property-read \App\Models\ManifestMetadata $manifest_metadata
 */
class ManifestTag extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'manifest_metadata_id'];

    public function manifest_metadata(){
        return $this->belongsTo(ManifestMetadata::class);
    }
}
