import { describe, expect, it } from 'vitest';
import { serializeHtml } from './quillSerializer';

/**
 * quill itself is never mounted here -- serializeHtml only needs an object
 * with a `.root` DOM node, so a bare div stands in for the real editor.
 */
function fakeQuill(innerHTML) {
    const root = document.createElement('div');
    root.innerHTML = innerHTML;

    return { root };
}

describe('serializeHtml', () => {
    it('converts a bullet list into a real <ul>', () => {
        const html = serializeHtml(
            fakeQuill('<ol><li data-list="bullet">One</li><li data-list="bullet">Two</li></ol>'),
        );

        expect(html).toBe('<ul><li>One</li><li>Two</li></ul>');
    });

    it('converts an ordered list into a real <ol>', () => {
        const html = serializeHtml(
            fakeQuill('<ol><li data-list="ordered">One</li><li data-list="ordered">Two</li></ol>'),
        );

        expect(html).toBe('<ol><li>One</li><li>Two</li></ol>');
    });

    it('splits consecutive bullet and ordered runs into separate lists', () => {
        const html = serializeHtml(
            fakeQuill(
                '<ol>'
                + '<li data-list="bullet">A</li>'
                + '<li data-list="bullet">B</li>'
                + '<li data-list="ordered">C</li>'
                + '<li data-list="ordered">D</li>'
                + '</ol>',
            ),
        );

        expect(html).toBe('<ul><li>A</li><li>B</li></ul><ol><li>C</li><li>D</li></ol>');
    });

    it('strips Quill 2 internal .ql-ui marker spans', () => {
        const html = serializeHtml(
            fakeQuill(
                '<ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Item</li></ol>',
            ),
        );

        expect(html).toBe('<ul><li>Item</li></ul>');
    });

    it('leaves non-list content untouched', () => {
        const html = serializeHtml(fakeQuill('<p>Plain paragraph.</p><p><strong>Bold.</strong></p>'));

        expect(html).toBe('<p>Plain paragraph.</p><p><strong>Bold.</strong></p>');
    });

    it('handles an editor with no lists at all', () => {
        const html = serializeHtml(fakeQuill('<p></p>'));

        expect(html).toBe('<p></p>');
    });
});
