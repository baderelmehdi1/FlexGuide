import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import AnnotationEditor from './AnnotationEditor.vue';

const router = vi.hoisted(() => ({ patch: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({
    router,
    usePage: () => ({ props: { translations: {} } }),
}));
vi.mock('../../../vendor/tightenco/ziggy', () => ({
    route: (_name, id) => `/media/${id}/annotations`,
}));

let wrapper;

function button(text) {
    return wrapper.findAll('button').find((item) => item.text() === text);
}

function mountEditor(annotations = []) {
    const media = reactive({ id: 12, url: '/test-image.png', annotations });
    wrapper = mount(AnnotationEditor, { props: { media } });
    vi.spyOn(wrapper.find('img').element, 'getBoundingClientRect').mockReturnValue({
        left: 0, top: 0, width: 200, height: 100,
    });

    return media;
}

beforeEach(() => {
    vi.clearAllMocks();
});

afterEach(() => {
    wrapper?.unmount();
    vi.restoreAllMocks();
});

describe('annotation editor with reactive screenshot data', () => {
    it('opens an image with no saved annotations', () => {
        mountEditor();

        expect(wrapper.find('h2').text()).toBe('Annotate');
        expect(wrapper.findAll('li')).toHaveLength(0);
        expect(button('Save annotations').exists()).toBe(true);
    });

    it('opens existing shapes and keeps removals local when cancelled', async () => {
        const savedShapes = [
            { type: 'box', x: 10, y: 10, w: 20, h: 15 },
            { type: 'point', x: 50, y: 50, text: 'اضغط هنا' },
        ];
        const media = mountEditor(savedShapes);
        expect(wrapper.findAll('li')).toHaveLength(2);

        await wrapper.find('li button').trigger('click');
        expect(wrapper.findAll('li')).toHaveLength(1);
        expect(media.annotations).toEqual(savedShapes);
        await button('Cancel').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
        expect(router.patch).not.toHaveBeenCalled();
        wrapper.unmount();
        wrapper = mount(AnnotationEditor, { props: { media } });
        expect(wrapper.findAll('li')).toHaveLength(2);
    });

    it('submits drawn boxes and labeled points and closes only after success', async () => {
        const media = mountEditor();
        const image = wrapper.find('img');
        await image.trigger('mousedown', { clientX: 20, clientY: 10 });
        await image.trigger('mousemove', { clientX: 60, clientY: 30 });
        await image.trigger('mouseup');
        await button('Point').trigger('click');
        vi.spyOn(window, 'prompt').mockReturnValue('اضغط هنا');
        await image.trigger('mousedown', { clientX: 100, clientY: 50 });
        await button('Save annotations').trigger('click');

        const expectedShapes = [
            { type: 'box', x: 10, y: 10, w: 20, h: 20 },
            { type: 'point', x: 50, y: 50, text: 'اضغط هنا' },
        ];
        expect(router.patch).toHaveBeenCalledWith(
            '/media/12/annotations',
            { shapes: expectedShapes },
            expect.objectContaining({ preserveScroll: true }),
        );
        expect(media.annotations).toEqual([]);
        expect(wrapper.emitted('close')).toBeUndefined();
        expect(button('Save annotations').attributes('disabled')).toBeDefined();

        const callbacks = router.patch.mock.calls[0][2];
        callbacks.onSuccess();
        callbacks.onFinish();
        await nextTick();
        expect(wrapper.emitted('close')).toHaveLength(1);

        // Reopening with the saved server response must display both shapes.
        wrapper.unmount();
        media.annotations = expectedShapes;
        wrapper = mount(AnnotationEditor, { props: { media } });
        expect(wrapper.findAll('li')).toHaveLength(2);
        expect(wrapper.text()).toContain('اضغط هنا');
    });

    it('keeps the editor open and original annotations unchanged after a failed save', async () => {
        const savedShapes = [{ type: 'box', x: 10, y: 10, w: 20, h: 15 }];
        const media = mountEditor(savedShapes);
        await wrapper.find('li button').trigger('click');
        await button('Save annotations').trigger('click');
        expect(router.patch.mock.calls[0][1]).toEqual({ shapes: [] });

        router.patch.mock.calls[0][2].onFinish();
        await nextTick();
        expect(wrapper.emitted('close')).toBeUndefined();
        expect(button('Save annotations').attributes('disabled')).toBeUndefined();
        expect(media.annotations).toEqual(savedShapes);
    });
});
