<?php

namespace App\Http\Middleware;

use App\Enums\Locale as AppLocale;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props available to every Vue page without each controller repeating
     * them -- the frontend equivalent of what a Blade layout could once pull
     * inline via auth()->user()/app()->getLocale(); Vue has no access to
     * either, so anything the nav chrome needs crosses the props boundary
     * explicitly.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = AppLocale::tryFrom(app()->getLocale()) ?? AppLocale::default();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->all(),
                    'isContributor' => $user->isContributor(),
                    'isApprover' => $user->isApprover(),
                    'isAdmin' => $user->isAdmin(),
                ] : null,
            ],

            'locale' => [
                'current' => $locale->value,
                'direction' => $locale->direction(),
                'options' => collect(AppLocale::cases())->map(fn (AppLocale $l) => [
                    'value' => $l->value,
                    'label' => $l->nativeLabel(),
                ]),
            ],

            /*
             | Flat key => translation map for the active locale, so Vue can
             | translate without a round trip. English strings double as their
             | own keys (see lang/ar.json), matching how __() already works.
             */
            'translations' => fn () => $this->translationsFor($locale),

            /*
             | ->with('status', ...) in a controller only flashes into the
             | session -- Inertia does not expose session flash data as a prop
             | on its own, so it is read back out and shared explicitly here.
             */
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],

            /*
             | The persistent sidebar (AppLayout.vue) needs the category tree
             | on every authenticated page, not just the guide index -- shared
             | globally rather than each controller repeating the query.
             */
            'sidebarCategories' => fn () => $user ? Category::tree() : [],
        ];
    }

    /** @return array<string, string> */
    private function translationsFor(AppLocale $locale): array
    {
        if ($locale === AppLocale::English) {
            return [];
        }

        $path = lang_path($locale->value.'.json');

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }
}
