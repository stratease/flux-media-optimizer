import React from 'react';
import { Chip, Tooltip } from '@mui/material';

/**
 * Shared format/capability status chip (Overview processors and Welcome gaps).
 *
 * @since 4.3.1
 * @param {Object} props Component props.
 * @param {string} props.label Chip label.
 * @param {boolean} props.supported Whether the capability is available.
 * @param {string} [props.tooltip] Optional tooltip title.
 * @param {string} props.capabilityKey Stable key for data attributes.
 * @return {JSX.Element} Capability chip.
 */
const CapabilityChip = ({ label, supported, tooltip, capabilityKey }) => {
  const chip = (
    <Chip
      label={label}
      color={supported ? 'success' : 'error'}
      size="small"
      data-flux-capability={capabilityKey}
      {...(supported ? {} : { 'data-flux-capability-missing': '1' })}
    />
  );

  if (!tooltip) {
    return chip;
  }

  return (
    <Tooltip title={tooltip} arrow>
      {chip}
    </Tooltip>
  );
};

export default CapabilityChip;
