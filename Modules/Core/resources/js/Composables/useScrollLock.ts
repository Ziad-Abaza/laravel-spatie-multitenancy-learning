/**
 * Reference-counted body scroll lock. Multiple overlays (modals, drawers)
 * may be open at once — scrolling is only restored when the last lock is
 * released. Callers hold no independent boolean flags.
 */
let lockCount = 0;
let previousOverflow: string | null = null;

export function lockScroll(): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (lockCount === 0) {
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }
    lockCount++;
}

export function unlockScroll(): void {
    if (typeof document === 'undefined' || lockCount === 0) {
        return;
    }

    lockCount--;
    if (lockCount === 0) {
        document.body.style.overflow = previousOverflow ?? '';
        previousOverflow = null;
    }
}
