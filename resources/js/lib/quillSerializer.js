/**
 * Quill 2 always wraps list items in a single <ol>, marking each <li> with
 * data-list="bullet"|"ordered" instead of ever emitting a real <ul> -- see
 * quill's list.js formats/list blot. This walks a clone of the editor's DOM
 * and regroups consecutive same-type items into real <ul>/<ol> elements
 * before the HTML leaves the browser, so what's stored and what's rendered
 * in the read-only viewer is plain semantic markup.
 *
 * Also strips the `.ql-ui` marker spans Quill 2 injects into each <li> for
 * its own bullet/number rendering -- editor-internal UI, not content.
 */
export function serializeHtml(quill) {
    const clone = quill.root.cloneNode(true);

    clone.querySelectorAll('.ql-ui').forEach((el) => el.remove());

    Array.from(clone.querySelectorAll('ol')).forEach((originalList) => {
        if (!originalList.parentNode) {
            return;
        }

        const items = Array.from(originalList.children).filter(
            (el) => el.tagName === 'LI' && el.hasAttribute('data-list'),
        );

        if (!items.length) {
            return;
        }

        const fragment = document.createDocumentFragment();
        let currentGroup = null;
        let currentType = null;

        items.forEach((li) => {
            const type = li.getAttribute('data-list') === 'ordered' ? 'ol' : 'ul';
            li.removeAttribute('data-list');

            if (type !== currentType) {
                currentGroup = document.createElement(type);
                fragment.appendChild(currentGroup);
                currentType = type;
            }

            currentGroup.appendChild(li);
        });

        originalList.replaceWith(fragment);
    });

    return clone.innerHTML;
}
