import React from 'react';
import { Chip } from '@mui/material';
import { CheckCircle, Error } from '@mui/icons-material';
import { __ } from '@wordpress/i18n';

/**
 * Shared Available / Not Available status chip for image and video processing.
 *
 * @since 4.3.1
 * @param {Object} props Component props.
 * @param {boolean} props.available Whether processing is available.
 * @param {string} props.type Label prefix (e.g. "Image Processing").
 * @return {JSX.Element} Status chip.
 */
const ProcessingAvailabilityChip = ({ available, type }) => {
  return (
    <Chip
      icon={available ? <CheckCircle color="success" /> : <Error color="error" />}
      label={
        available
          ? `${type} ${__('Available', 'flux-media-optimizer')}`
          : `${type} ${__('Not Available', 'flux-media-optimizer')}`
      }
      color={available ? 'success' : 'error'}
      size="small"
      data-flux-processing-chip={available ? 'available' : 'unavailable'}
      data-flux-processing-type={type}
    />
  );
};

export default ProcessingAvailabilityChip;
