<?php

namespace App\Http\Controllers;

use App\Enums\GuideStatus;
use App\Enums\Locale;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GuideBuilderController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('Guides/Create', [
            'categories' => $this->flatCategories(),
            'languages' => Locale::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'language' => ['required', Rule::in(Locale::values())],
        ]);

        $guide = Guide::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['title']),
            'status' => GuideStatus::Draft,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('guides.edit', $guide);
    }

    public function edit(Guide $guide): InertiaResponse
    {
        $this->authorize('update', $guide);

        $guide->load(['steps.media', 'tags:id,name']);

        return Inertia::render('Guides/Builder', [
            'guide' => [
                'id' => $guide->id,
                'slug' => $guide->slug,
                'title' => $guide->title,
                'description' => $guide->description,
                'language' => $guide->language,
                'category_id' => $guide->category_id,
                'status' => $guide->status->value,
                'statusLabel' => $guide->status->label(),
                'tags' => $guide->tags->pluck('name'),
            ],
            'steps' => $guide->steps->map(fn ($step) => [
                'id' => $step->id,
                'order' => $step->order,
                'title' => $step->title,
                'body' => $step->body,
                'warning' => $step->warning,
                'screenshots' => $step->getMedia('screenshots')->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl().'?v='.$media->updated_at->timestamp,
                    'thumbUrl' => $media->getUrl('thumb').'?v='.$media->updated_at->timestamp,
                    'annotations' => $media->getCustomProperty('annotations', []),
                ]),
            ]),
            'categories' => $this->flatCategories(),
            'languages' => Locale::values(),
        ]);
    }

    public function update(Request $request, Guide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'language' => ['required', Rule::in(Locale::values())],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $guide->update([
            ...collect($data)->except('tags')->all(),
            'updated_by' => $request->user()->id,
        ]);

        $tagIds = collect($data['tags'] ?? [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id);

        $guide->tags()->sync($tagIds);

        return back()->with('status', 'Guide details saved.');
    }

    public function submitForReview(Request $request, Guide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        abort_unless($guide->status === GuideStatus::Draft, 422, 'Only draft guides can be submitted for review.');
        abort_if($guide->steps()->count() === 0, 422, 'Add at least one step before submitting for review.');

        $guide->update([
            'status' => GuideStatus::Pending,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('guides.show', $guide)->with('status', 'Submitted for review.');
    }

    /** @return array<int, array<string, mixed>> */
    private function flatCategories(): array
    {
        return Category::query()
            ->orderBy('parent_id')
            ->orderBy('order')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->parent_id ? '— '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 1;

        while (Guide::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
