<!DOCTYPE html>
<html dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: {{ $rtl ? 'plexarabic' : 'dejavusans' }}, sans-serif;
        font-size: 11pt;
        color: #0a1a3a;
        direction: {{ $rtl ? 'rtl' : 'ltr' }};
        text-align: {{ $rtl ? 'right' : 'left' }};
    }
    h1 { font-size: 18pt; color: #0a1a3a; margin-bottom: 4pt; }
    .meta { font-size: 9pt; color: #545f75; margin-bottom: 16pt; }
    .step { margin-bottom: 18pt; page-break-inside: avoid; }
    .step-title {
        font-size: 13pt;
        font-weight: bold;
        color: #0a1a3a;
        background-color: #f1f5f9;
        padding: 4pt 8pt;
        margin-bottom: 6pt;
    }
    .warning {
        border-{{ $rtl ? 'right' : 'left' }}: 3pt solid #daaf37;
        background-color: #fdf6e3;
        padding: 6pt 10pt;
        margin-bottom: 6pt;
        font-size: 10pt;
    }
    .body p { margin: 0 0 6pt 0; }
    .body ul, .body ol { margin: 0 0 6pt 0; }
    img.screenshot { max-width: 100%; border: 1pt solid #cbd5e1; margin-top: 6pt; }
</style>
</head>
<body>
    <h1>{{ $guide->title }}</h1>
    <p class="meta">
        {{ $guide->category?->name }}
        @if($guide->description)
            &mdash; {{ $guide->description }}
        @endif
    </p>

    @foreach($steps as $index => $step)
        <div class="step">
            <div class="step-title">{{ $index + 1 }}. {{ $step['title'] ?? '' }}</div>

            @if($step['warning'])
                <div class="warning"><strong>{{ $rtl ? 'تنبيه' : 'Warning' }}:</strong> {{ $step['warning'] }}</div>
            @endif

            @if($step['body'])
                <div class="body">{!! $step['body'] !!}</div>
            @endif

            @foreach($step['screenshots'] as $screenshotPath)
                <img class="screenshot" src="{{ $screenshotPath }}">
            @endforeach
        </div>
    @endforeach
</body>
</html>
