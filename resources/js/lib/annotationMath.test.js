import { describe, expect, it } from 'vitest';
import { clientToPercent, isNegligibleDrag, normalizeBox } from './annotationMath';

describe('clientToPercent', () => {
    const rect = { left: 100, top: 50, width: 400, height: 200 };

    it('maps a client point to a percentage of the bounding box', () => {
        expect(clientToPercent(rect, 100, 50)).toEqual({ x: 0, y: 0 });
        expect(clientToPercent(rect, 500, 250)).toEqual({ x: 100, y: 100 });
        expect(clientToPercent(rect, 300, 150)).toEqual({ x: 50, y: 50 });
    });

    it('clamps points outside the image to 0-100', () => {
        expect(clientToPercent(rect, -50, -50)).toEqual({ x: 0, y: 0 });
        expect(clientToPercent(rect, 9999, 9999)).toEqual({ x: 100, y: 100 });
    });
});

describe('normalizeBox', () => {
    it('leaves a top-left-to-bottom-right drag unchanged', () => {
        expect(normalizeBox({ x: 10, y: 10, w: 20, h: 15 })).toEqual({ x: 10, y: 10, w: 20, h: 15 });
    });

    it('normalizes a drag toward the top-left into positive width/height', () => {
        // dragged from (30, 30) up-and-left to (10, 15): w=-20, h=-15
        expect(normalizeBox({ x: 30, y: 30, w: -20, h: -15 })).toEqual({ x: 10, y: 15, w: 20, h: 15 });
    });

    it('normalizes a drag toward the top-right', () => {
        expect(normalizeBox({ x: 10, y: 30, w: 20, h: -15 })).toEqual({ x: 10, y: 15, w: 20, h: 15 });
    });

    it('normalizes a drag toward the bottom-left', () => {
        expect(normalizeBox({ x: 30, y: 10, w: -20, h: 15 })).toEqual({ x: 10, y: 10, w: 20, h: 15 });
    });
});

describe('isNegligibleDrag', () => {
    it('treats sub-threshold drags as accidental clicks', () => {
        expect(isNegligibleDrag({ w: 0.5, h: 0.5 })).toBe(true);
        expect(isNegligibleDrag({ w: 5, h: 0.2 })).toBe(true);
    });

    it('treats drags past the threshold as real boxes', () => {
        expect(isNegligibleDrag({ w: 5, h: 5 })).toBe(false);
        expect(isNegligibleDrag({ w: -5, h: -5 })).toBe(false);
    });

    it('respects a custom threshold', () => {
        expect(isNegligibleDrag({ w: 3, h: 3 }, 5)).toBe(true);
        expect(isNegligibleDrag({ w: 6, h: 6 }, 5)).toBe(false);
    });
});
