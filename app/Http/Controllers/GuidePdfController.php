<?php

namespace App\Http\Controllers;

use App\Enums\GuideStatus;
use App\Models\Guide;
use App\Models\Step;
use App\Support\Pdf\AnnotationFlattener;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class GuidePdfController extends Controller
{
    public function show(Request $request, Guide $guide): Response
    {
        $user = $request->user();

        abort_unless(
            $guide->status === GuideStatus::Published
                || $user?->isApprover()
                || $guide->created_by === $user?->id,
            403,
        );

        $guide->load(['category', 'steps.media']);

        $tempFiles = [];

        $steps = $guide->steps->map(function (Step $step) use (&$tempFiles) {
            $screenshots = $step->getMedia('screenshots')->map(function ($media) use (&$tempFiles) {
                $annotations = $media->getCustomProperty('annotations', []);
                $path = AnnotationFlattener::flatten($media->getPath(), $media->mime_type, $annotations);

                if ($path !== $media->getPath()) {
                    $tempFiles[] = $path;
                }

                return $path;
            });

            return [
                'title' => $step->title,
                'body' => $step->body,
                'warning' => $step->warning,
                'screenshots' => $screenshots,
            ];
        });

        $rtl = $guide->language === 'ar';

        $html = view('pdf.guide', [
            'guide' => $guide,
            'steps' => $steps,
            'rtl' => $rtl,
        ])->render();

        $mpdf = new Mpdf($this->mpdfConfig($rtl));
        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output($guide->slug.'.pdf', Destination::STRING_RETURN);

        foreach ($tempFiles as $path) {
            @unlink($path);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$guide->slug.'.pdf"',
        ]);
    }

    /** @return array<string, mixed> */
    private function mpdfConfig(bool $rtl): array
    {
        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];

        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => $rtl ? 'rtl' : 'ltr',
            'fontDir' => array_merge($fontDirs, [resource_path('fonts')]),
            'fontdata' => $fontData + [
                // IBM Plex Sans Arabic -- static (not variable, unlike Noto
                // Sans Arabic originally tried, which rendered garbled).
                // useOTL enables mPDF's OpenType Layout engine, needed to
                // join Arabic letters into their contextual forms; without
                // it, letters render isolated regardless of font. Lateef
                // (an mPDF-known-compatible font) worked correctly but its
                // thin calligraphic strokes read as blurry at body text
                // size; this has cleaner, more uniform strokes closer to
                // the Noto Sans Arabic used on-screen.
                'plexarabic' => [
                    'R' => 'IBMPlexSansArabic-Regular.ttf',
                    'B' => 'IBMPlexSansArabic-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => $rtl ? 'plexarabic' : 'dejavusans',
        ];
    }
}
