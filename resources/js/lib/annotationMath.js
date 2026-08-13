/**
 * Shared by AnnotationEditor and RedactEditor: both draw shapes by tracking
 * mouse position as a percentage of the image's own bounding box, so
 * coordinates stay correct regardless of the image's displayed size or
 * aspect ratio (see AnnotationOverlay's viewBox="0 0 100 100" trick).
 */
export function clientToPercent(boundingRect, clientX, clientY) {
    return {
        x: clamp(((clientX - boundingRect.left) / boundingRect.width) * 100),
        y: clamp(((clientY - boundingRect.top) / boundingRect.height) * 100),
    };
}

function clamp(value) {
    return Math.min(100, Math.max(0, value));
}

/**
 * A box can be dragged in any of the four directions, producing a negative
 * width and/or height relative to the mousedown origin. Normalizes to a
 * top-left origin with positive width/height, as stored on the server.
 */
export function normalizeBox({ x, y, w, h }) {
    return {
        x: w < 0 ? x + w : x,
        y: h < 0 ? y + h : y,
        w: Math.abs(w),
        h: Math.abs(h),
    };
}

/** Below this, a drag is treated as an accidental click rather than a box. */
export function isNegligibleDrag({ w, h }, thresholdPercent = 1) {
    return Math.abs(w) < thresholdPercent || Math.abs(h) < thresholdPercent;
}
