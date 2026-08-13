<?php

namespace App\Http\Controllers;

use App\Enums\GuideStatus;
use App\Models\Guide;
use App\Models\Step;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReviewController extends Controller
{
    public function index(): InertiaResponse
    {
        $guides = Guide::query()
            ->where('status', GuideStatus::Pending)
            ->with(['category:id,name', 'author:id,name'])
            ->oldest('updated_at')
            ->get()
            ->map(fn (Guide $guide) => [
                'slug' => $guide->slug,
                'title' => $guide->title,
                'category' => $guide->category?->name,
                'author' => $guide->author?->name,
                'language' => $guide->language,
                'updatedAt' => $guide->updated_at?->toIso8601String(),
                'stepsCount' => $guide->steps()->count(),
            ]);

        return Inertia::render('Review/Index', [
            'guides' => $guides,
        ]);
    }

    public function publish(Request $request, Guide $guide): RedirectResponse
    {
        $this->authorize('publish', $guide);

        abort_unless($guide->status === GuideStatus::Pending, 422, 'Only guides pending review can be published.');

        DB::transaction(function () use ($guide, $request) {
            $guide->load(['steps.media', 'tags']);

            $snapshot = [
                'title' => $guide->title,
                'description' => $guide->description,
                'language' => $guide->language,
                'category_id' => $guide->category_id,
                'tags' => $guide->tags->pluck('name')->all(),
                'steps' => $guide->steps->map(fn (Step $step) => [
                    'order' => $step->order,
                    'title' => $step->title,
                    'body' => $step->body,
                    'warning' => $step->warning,
                    'screenshots' => $step->getMedia('screenshots')->map(fn ($media) => [
                        'url' => $media->getUrl(),
                        'annotations' => $media->getCustomProperty('annotations', []),
                    ])->values()->all(),
                ])->values()->all(),
            ];

            $guide->versions()->create([
                'snapshot' => $snapshot,
                'created_by' => $request->user()->id,
            ]);

            $guide->update([
                'status' => GuideStatus::Published,
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('review.index')->with('status', 'Guide published.');
    }

    public function sendBack(Request $request, Guide $guide): RedirectResponse
    {
        $this->authorize('publish', $guide);

        abort_unless($guide->status === GuideStatus::Pending, 422, 'Only guides pending review can be sent back.');

        $guide->update([
            'status' => GuideStatus::Draft,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('review.index')->with('status', 'Sent back to draft.');
    }
}
