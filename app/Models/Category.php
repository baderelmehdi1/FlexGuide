<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    public function guides(): HasMany
    {
        return $this->hasMany(Guide::class);
    }

    /**
     * Nested category tree with guide counts, for the persistent sidebar
     * nav (shared globally, see HandleInertiaRequests) and the guide index
     * page. A flat query grouped in memory rather than N+1 recursive
     * queries -- the category count in this app is small enough that
     * building the whole tree in one pass is cheap either way.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tree(): array
    {
        $categories = static::query()->withCount('guides')->orderBy('order')->get();
        $byParent = $categories->groupBy('parent_id');

        $build = function (?int $parentId) use (&$build, $byParent) {
            return $byParent->get($parentId, collect())
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'guidesCount' => $category->guides_count,
                    'children' => $build($category->id),
                ])
                ->values()
                ->all();
        };

        return $build(null);
    }
}
