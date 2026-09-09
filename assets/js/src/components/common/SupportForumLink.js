import React from 'react';
import { Link } from '@mui/material';

/**
 * Outbound WordPress.org support forum link.
 *
 * Label is passed by callers so admin can translate and the attachment island
 * stays free of `@wordpress/i18n` imports.
 *
 * @since 4.3.1
 * @param {Object} props Component props.
 * @param {string} [props.href] Support URL; falls back to localized fluxMediaAdmin.supportUrl.
 * @param {string} [props.label='Need help?'] Link label.
 * @return {JSX.Element|null} Support forum link or null when URL missing.
 */
const SupportForumLink = ({ href, label = 'Need help?' }) => {
  const url = href || window.fluxMediaAdmin?.supportUrl || '';

  if (!url) {
    return null;
  }

  return (
    <Link
      href={url}
      target="_blank"
      rel="noopener noreferrer"
      underline="hover"
      data-flux-support-link="1"
    >
      {label}
    </Link>
  );
};

export default SupportForumLink;
