import { useEffect, useRef, useState } from 'react';

/**
 * Once-ever modal open state with mark-viewed on first open.
 *
 * Page-agnostic: pass initiallyOpen and an async onMarkViewed callback.
 *
 * @since 4.3.1
 * @param {Object} options Hook options.
 * @param {boolean} options.initiallyOpen Whether the modal should open on mount.
 * @param {Function} options.onMarkViewed Async callback invoked once when open becomes true.
 * @return {{ open: boolean, setOpen: Function }} Modal open state controls.
 */
export const useOnceEverModal = ({ initiallyOpen, onMarkViewed }) => {
  const [open, setOpen] = useState(() => Boolean(initiallyOpen));
  const markedRef = useRef(false);

  useEffect(() => {
    if (!open || markedRef.current) {
      return;
    }

    markedRef.current = true;

    if (typeof onMarkViewed !== 'function') {
      return;
    }

    Promise.resolve(onMarkViewed()).catch(() => {
      // Non-blocking: dialog still works if mark-viewed fails.
    });
  }, [open, onMarkViewed]);

  return { open, setOpen };
};
