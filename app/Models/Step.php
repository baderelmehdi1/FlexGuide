<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Step extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'guide_id',
        'order',
        'title',
        'body',
        'warning',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('screenshots');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->sharpen(10)
            ->nonQueued();
    }
}
