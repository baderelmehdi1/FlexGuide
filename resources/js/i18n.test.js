import { describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

const pageMock = reactive({ props: { locale: { current: 'ar', direction: 'rtl' } } });

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageMock,
}));

const { useSyncDocumentDirection } = await import('./i18n');

describe('useSyncDocumentDirection', () => {
    it('sets <html dir/lang> immediately from the current locale', () => {
        useSyncDocumentDirection();

        expect(document.documentElement.getAttribute('dir')).toBe('rtl');
        expect(document.documentElement.getAttribute('lang')).toBe('ar');
    });

    it('updates <html dir/lang> when the locale prop changes after a switch', async () => {
        useSyncDocumentDirection();

        pageMock.props.locale = { current: 'en', direction: 'ltr' };
        await nextTick();

        expect(document.documentElement.getAttribute('dir')).toBe('ltr');
        expect(document.documentElement.getAttribute('lang')).toBe('en');
    });
});
