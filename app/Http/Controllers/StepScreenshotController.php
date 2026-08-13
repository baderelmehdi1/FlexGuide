<?php

namespace App\Http\Controllers;

use App\Models\Step;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StepScreenshotController extends Controller
{
    /**
     * FilePond's own XHR client hits this directly, outside Inertia's
     * request cycle -- it expects a bare response body (the new file's id)
     * rather than an Inertia page, per FilePond's server.process contract.
     */
    public function store(Request $request, Step $step): Response
    {
        $this->authorize('update', $step->guide);

        $request->validate([
            'file' => ['required', 'image', 'max:10240'],
        ]);

        $media = $step->addMediaFromRequest('file')->toMediaCollection('screenshots');

        return response((string) $media->id, 201)->header('Content-Type', 'text/plain');
    }

    public function destroy(Step $step, Media $media): RedirectResponse
    {
        $this->authorize('update', $step->guide);

        abort_unless($media->model_id === $step->id && $media->model_type === Step::class, 404);

        $media->delete();

        return back();
    }

    /**
     * Non-destructive overlay shapes, stored as JSON on the media record
     * itself rather than a separate table -- they are pure display metadata
     * with no independent lifecycle of their own.
     */
    public function annotate(Request $request, Media $media): RedirectResponse
    {
        $step = $media->model;
        abort_unless($step instanceof Step, 404);
        $this->authorize('update', $step->guide);

        $data = $request->validate([
            'shapes' => ['present', 'array'],
            'shapes.*.type' => ['required', 'string', 'in:box,point'],
            'shapes.*.x' => ['required', 'numeric', 'between:0,100'],
            'shapes.*.y' => ['required', 'numeric', 'between:0,100'],
            'shapes.*.w' => ['nullable', 'numeric', 'between:0,100'],
            'shapes.*.h' => ['nullable', 'numeric', 'between:0,100'],
            'shapes.*.text' => ['nullable', 'string', 'max:200'],
        ]);

        $media->setCustomProperty('annotations', $data['shapes']);
        $media->save();

        return back();
    }

    /**
     * Burns solid black rectangles directly into the stored image and
     * overwrites it -- unlike annotate(), there is no separate metadata
     * layer to undo. Per the redaction policy: author + approvers only,
     * replace the original, keep no recoverable copy.
     */
    public function redact(Request $request, Media $media, FileManipulator $fileManipulator): RedirectResponse
    {
        $step = $media->model;
        abort_unless($step instanceof Step, 404);
        $this->authorize('update', $step->guide);

        $data = $request->validate([
            'boxes' => ['required', 'array', 'min:1'],
            'boxes.*.x' => ['required', 'numeric', 'between:0,100'],
            'boxes.*.y' => ['required', 'numeric', 'between:0,100'],
            'boxes.*.w' => ['required', 'numeric', 'between:0,100'],
            'boxes.*.h' => ['required', 'numeric', 'between:0,100'],
        ]);

        $path = $media->getPath();

        $image = match ($media->mime_type) {
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => imagecreatefromjpeg($path),
        };

        abort_unless($image, 422, 'Unable to process image for redaction.');

        $width = imagesx($image);
        $height = imagesy($image);
        $black = imagecolorallocate($image, 0, 0, 0);

        foreach ($data['boxes'] as $box) {
            imagefilledrectangle(
                $image,
                (int) round($box['x'] / 100 * $width),
                (int) round($box['y'] / 100 * $height),
                (int) round(($box['x'] + $box['w']) / 100 * $width),
                (int) round(($box['y'] + $box['h']) / 100 * $height),
                $black,
            );
        }

        match ($media->mime_type) {
            'image/png' => imagepng($image, $path),
            'image/webp' => imagewebp($image, $path),
            default => imagejpeg($image, $path, 90),
        };

        imagedestroy($image);

        $media->touch();

        $fileManipulator->createDerivedFiles($media);

        return back()->with('status', 'Redaction applied.');
    }
}
