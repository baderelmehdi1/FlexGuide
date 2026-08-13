<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Step;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StepController extends Controller
{
    public function store(Guide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        $nextOrder = (int) $guide->steps()->max('order') + 1;

        $guide->steps()->create([
            'order' => $nextOrder,
            'title' => null,
            'body' => null,
            'warning' => null,
        ]);

        return back();
    }

    public function update(Request $request, Step $step): RedirectResponse
    {
        $this->authorize('update', $step->guide);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'warning' => ['nullable', 'string', 'max:500'],
        ]);

        $step->update([
            'title' => $data['title'] ?? null,
            'body' => HtmlSanitizer::clean($data['body'] ?? null),
            'warning' => $data['warning'] ?? null,
        ]);

        return back()->with('status', 'Step saved.');
    }

    public function destroy(Step $step): RedirectResponse
    {
        $this->authorize('update', $step->guide);

        $step->delete();

        return back();
    }

    public function reorder(Request $request, Guide $guide): RedirectResponse
    {
        $this->authorize('update', $guide);

        $data = $request->validate([
            'stepIds' => ['required', 'array'],
            'stepIds.*' => ['integer', 'exists:steps,id'],
        ]);

        $stepsById = $guide->steps()->whereIn('id', $data['stepIds'])->get()->keyBy('id');

        foreach ($data['stepIds'] as $index => $stepId) {
            $stepsById->get($stepId)?->update(['order' => $index + 1]);
        }

        return back();
    }
}
