/**
 * Attachment details React island entry.
 *
 * Mounts AttachmentDetails panels with TanStack Query and keeps deprecated
 * global AJAX helpers as thin delegates for external compatibility.
 *
 * Classic edit and media modals mount via PHP attachment_fields_to_edit →
 * AttachmentCompat. This entry only hydrates existing mount nodes.
 *
 * @since 4.3.0
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider } from '@mui/material/styles';
import theme from '@flux-plugins-common/theme';
import AttachmentDetails from '../attachment/AttachmentDetails';
import {
  convertAttachment,
  disableConversion,
  enableConversion,
} from '../attachment/attachmentApi';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

/**
 * Convert a specific attachment via AJAX.
 *
 * @deprecated 4.3.0 Prefer React panel actions; retained for external callers.
 * @since 0.1.0
 * @param {number} attachmentId Attachment ID.
 * @returns {Promise<boolean>}
 */
export function fluxMediaConvertAttachment(attachmentId) {
  return convertAttachment(attachmentId)
    .then(() => true)
    .catch((error) => {
      window.alert(error?.message || 'Conversion request failed');
      return false;
    });
}

/**
 * Disable conversion for an attachment.
 *
 * @deprecated 4.3.0 Prefer React panel actions; retained for external callers.
 * @since 0.1.0
 * @param {number} attachmentId Attachment ID.
 * @returns {Promise<boolean>}
 */
export function fluxMediaDisableConversion(attachmentId) {
  return disableConversion(attachmentId)
    .then(() => true)
    .catch(() => false);
}

/**
 * Enable conversion for an attachment.
 *
 * @deprecated 4.3.0 Prefer React panel actions; retained for external callers.
 * @since 0.1.0
 * @param {number} attachmentId Attachment ID.
 * @returns {Promise<boolean>}
 */
export function fluxMediaEnableConversion(attachmentId) {
  return enableConversion(attachmentId)
    .then(() => true)
    .catch(() => false);
}

window.fluxMediaConvertAttachment = fluxMediaConvertAttachment;
window.fluxMediaDisableConversion = fluxMediaDisableConversion;
window.fluxMediaEnableConversion = fluxMediaEnableConversion;

/**
 * Resolve attachment ID from a mount node.
 *
 * @since 4.3.0
 * @param {Element} mountNode Mount element.
 * @returns {number}
 */
function resolveAttachmentId(mountNode) {
  const fromMount = mountNode.getAttribute('data-flux-media-attachment-id');
  if (fromMount) {
    return parseInt(fromMount, 10) || 0;
  }

  const rootEl = mountNode.closest('[data-flux-media-attachment-root="1"]');
  const fromRoot = rootEl?.getAttribute('data-flux-media-attachment-id');
  return fromRoot ? parseInt(fromRoot, 10) || 0 : 0;
}

/**
 * Mount React islands for each attachment details root in a container.
 *
 * Idempotent: skips nodes already marked `data-flux-mounted`.
 *
 * @since 4.3.0
 * @param {ParentNode} [root=document] Scan root (document or mutation subtree).
 * @returns {void}
 */
function mountAttachmentIslands(root = document) {
  const roots = root.querySelectorAll
    ? root.querySelectorAll('[data-flux-media-attachment-app="1"]')
    : [];
  roots.forEach((mountNode) => {
    if (mountNode.dataset.fluxMounted === '1') {
      return;
    }

    const attachmentId = resolveAttachmentId(mountNode);
    if (!attachmentId) {
      return;
    }

    mountNode.dataset.fluxMounted = '1';
    const reactRoot = createRoot(mountNode);
    reactRoot.render(
      <QueryClientProvider client={queryClient}>
        <ThemeProvider theme={theme}>
          <AttachmentDetails attachmentId={attachmentId} />
        </ThemeProvider>
      </QueryClientProvider>
    );
  });
}

/**
 * Observe DOM for AttachmentCompat / classic fields injected after load.
 *
 * Prefer MutationObserver over patching wp.media.view.AttachmentCompat so
 * editor modals and classic screens mount without WordPress view inheritance.
 *
 * @since 4.3.0
 * @returns {void}
 */
function observeAttachmentMountPoints() {
  if (typeof MutationObserver === 'undefined' || !document.body) {
    return;
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) {
          return;
        }
        if (node.matches('[data-flux-media-attachment-app="1"]')) {
          mountAttachmentIslands(node.parentNode || document);
          return;
        }
        if (node.querySelector('[data-flux-media-attachment-app="1"]')) {
          mountAttachmentIslands(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

function bootAttachmentIslands() {
  mountAttachmentIslands();
  observeAttachmentMountPoints();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootAttachmentIslands);
} else {
  bootAttachmentIslands();
}

export { mountAttachmentIslands, observeAttachmentMountPoints };
