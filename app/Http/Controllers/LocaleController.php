<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Persisted in the session, not the URL or a cookie -- see SetLocale's
     * doc block: this is a per-session interface choice, independent of any
     * individual guide's own content language.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(Locale::values())],
        ]);

        $request->session()->put('locale', $data['locale']);

        return back();
    }
}
