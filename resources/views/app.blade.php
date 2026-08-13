<!DOCTYPE html>
<html lang="{{ $page['props']['locale']['current'] ?? 'ar' }}" dir="{{ $page['props']['locale']['direction'] ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name') }}</title>

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="min-h-screen bg-brand-white font-sans text-brand-navy antialiased">
    @inertia
</body>
</html>
