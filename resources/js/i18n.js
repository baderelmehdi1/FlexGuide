import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

/**
 * Translation lookup backed by a `translations` prop shared on every request
 * (see HandleInertiaRequests::share()) -- the flat key => value map for the
 * active locale, sourced from lang/ar.json. English strings double as their
 * own keys, so a missing translation falls back to the key itself, exactly
 * like Laravel's __() does.
 *
 * A composable rather than a global $t: globalProperties are not reachable
 * from <script setup> components without extra boilerplate, and every
 * component in this app uses <script setup>.
 *
 * Deliberately no pluralisation helper. Arabic has six plural forms; rather
 * than get that wrong, anywhere a count is shown is phrased to not need
 * grammatical agreement at all -- "Steps: 4" rather than "4 steps".
 */
export function useTranslations() {
    const page = usePage();

    function t(key, replacements = {}) {
        const table = page.props.translations ?? {};
        let value = table[key] ?? key;

        for (const [name, replacement] of Object.entries(replacements)) {
            value = value.replaceAll(`:${name}`, replacement);
        }

        return value;
    }

    return { t };
}

/** Reactive current-locale info (value/direction), from the same shared prop. */
export function useLocale() {
    const page = usePage();

    return computed(() => page.props.locale);
}

/**
 * app.blade.php sets <html lang dir> from the locale prop, but only on the
 * very first full page load -- switching locale via LocaleSwitcher.vue is a
 * normal Inertia (XHR) visit, which updates page.props reactively without
 * ever re-running Blade. Without this, dir stayed stuck at whatever it was
 * on first load (e.g. "rtl") no matter what locale you switched to.
 * Call once from each root layout; it patches the live <html> element.
 */
export function useSyncDocumentDirection() {
    const locale = useLocale();

    watch(
        locale,
        (value) => {
            document.documentElement.setAttribute('dir', value.direction);
            document.documentElement.setAttribute('lang', value.current);
        },
        { immediate: true },
    );
}
