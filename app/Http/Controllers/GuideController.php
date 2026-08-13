<?php

namespace App\Http\Controllers;

use App\Enums\GuideStatus;
use App\Models\Guide;
use App\Models\Step;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GuideController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $guides = Guide::query()
            ->with(['category:id,name,slug', 'tags:id,name'])
            ->when(! $user?->isContributor(), fn ($query) => $query->where('status', GuideStatus::Published))
            ->when(
                $user?->isContributor() && ! $user->isApprover(),
                fn ($query) => $query->where(fn ($q) => $q
                    ->where('status', GuideStatus::Published)
                    ->orWhere('created_by', $user->id)),
            )
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $request->string('category')),
            ))
            ->latest('updated_at')
            ->get()
            ->map(fn (Guide $guide) => [
                'id' => $guide->id,
                'title' => $guide->title,
                'slug' => $guide->slug,
                'description' => $guide->description,
                'language' => $guide->language,
                'status' => $guide->status->value,
                'statusLabel' => $guide->status->label(),
                'badgeClasses' => $guide->status->badgeClasses(),
                'category' => $guide->category ? [
                    'name' => $guide->category->name,
                    'slug' => $guide->category->slug,
                ] : null,
                'tags' => $guide->tags->pluck('name'),
                'updatedAt' => $guide->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Guides/Index', [
            'guides' => $guides,
            'activeCategory' => $request->string('category')->toString() ?: null,
        ]);
    }

    public function show(Request $request, Guide $guide): InertiaResponse
    {
        $user = $request->user();

        abort_unless(
            $guide->status === GuideStatus::Published
                || $user?->isApprover()
                || $guide->created_by === $user?->id,
            403,
        );

        $guide->load(['category:id,name,slug', 'tags:id,name', 'steps.media']);

        return Inertia::render('Guides/Show', [
            'guide' => [
                'id' => $guide->id,
                'slug' => $guide->slug,
                'title' => $guide->title,
                'description' => $guide->description,
                'language' => $guide->language,
                'status' => $guide->status->value,
                'statusLabel' => $guide->status->label(),
                'badgeClasses' => $guide->status->badgeClasses(),
                'category' => $guide->category ? [
                    'name' => $guide->category->name,
                    'slug' => $guide->category->slug,
                ] : null,
                'tags' => $guide->tags->pluck('name'),
                'canEdit' => (bool) $user && ($user->isApprover() || $guide->created_by === $user->id),
                'steps' => $guide->steps->map(fn (Step $step) => [
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
            ],
        ]);
    }

}
