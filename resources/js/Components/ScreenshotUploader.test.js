import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ScreenshotUploader from './ScreenshotUploader.vue';

const pond = vi.hoisted(() => ({
    create: vi.fn(),
    registerPlugin: vi.fn(),
    destroy: vi.fn(),
    removeFiles: vi.fn(),
}));

vi.mock('filepond', () => ({ create: pond.create, registerPlugin: pond.registerPlugin }));
vi.mock('filepond-plugin-file-validate-type', () => ({ default: {} }));
vi.mock('filepond-plugin-image-preview', () => ({ default: {} }));
vi.mock('@inertiajs/vue3', () => ({
    router: { reload: vi.fn(), delete: vi.fn(), patch: vi.fn() },
    usePage: () => ({ props: { translations: {} } }),
}));
vi.mock('../../../vendor/tightenco/ziggy', () => ({
    route: (_name, id) => `/steps/${id}/screenshots`,
}));

let wrapper;
let staleMeta;

function processOptions() {
    return pond.create.mock.calls[0][1].server.process;
}

function mountUploader() {
    wrapper = mount(ScreenshotUploader, { props: { stepId: 7, screenshots: [] } });
}

beforeEach(() => {
    vi.clearAllMocks();
    pond.create.mockReturnValue({ destroy: pond.destroy, removeFiles: pond.removeFiles });
    document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
    document.cookie = 'other-cookie=unrelated; path=/';
    staleMeta = document.createElement('meta');
    staleMeta.name = 'csrf-token';
    staleMeta.content = 'outdated-page-token';
    document.head.appendChild(staleMeta);
});

afterEach(() => {
    wrapper?.unmount();
    staleMeta.remove();
    document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
    document.cookie = 'other-cookie=; Max-Age=0; path=/';
});

describe('screenshot upload CSRF headers', () => {
    it('uses the decoded current cookie instead of the outdated page token', () => {
        document.cookie = `XSRF-TOKEN=${encodeURIComponent('fresh+cookie/value=')}; path=/`;
        mountUploader();

        expect(processOptions().headers()).toEqual({ 'X-XSRF-TOKEN': 'fresh+cookie/value=' });
    });

    it('reads the cookie again for each upload after the session token rotates', () => {
        document.cookie = 'XSRF-TOKEN=first-session-cookie; path=/';
        mountUploader();
        const headers = processOptions().headers;

        expect(headers()).toEqual({ 'X-XSRF-TOKEN': 'first-session-cookie' });
        document.cookie = 'XSRF-TOKEN=rotated-session-cookie; path=/';
        expect(headers()).toEqual({ 'X-XSRF-TOKEN': 'rotated-session-cookie' });
    });

    it('does not fall back to stale metadata when the cookie is missing', () => {
        mountUploader();

        expect(processOptions().headers()).toEqual({});
    });

    it('does not crash or send an invalid token when the cookie is malformed', () => {
        document.cookie = 'XSRF-TOKEN=%not-valid-encoding; path=/';
        mountUploader();

        expect(processOptions().headers()).toEqual({});
    });
});
