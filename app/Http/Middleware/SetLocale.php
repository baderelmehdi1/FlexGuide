<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the interface language for this request.
     *
     * Precedence: an explicit choice held in the session, then the app
     * default (Arabic). The browser's Accept-Language is deliberately
     * ignored -- staff share machines at the branch, and a device's locale is
     * a poor proxy for the language a particular teller wants to work in.
     *
     * Runs on every request and overwrites app()->setLocale() unconditionally
     * from session state -- if you ever set the locale manually earlier in a
     * request lifecycle (e.g. in a test's setUp()), this middleware will
     * still overwrite it when the request is actually dispatched. Force
     * locale in HTTP tests via ->withSession(['locale' => 'en']), not
     * app()->setLocale().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chosen = Locale::tryFrom((string) $request->session()->get('locale'));

        app()->setLocale(($chosen ?? Locale::default())->value);

        return $next($request);
    }
}
