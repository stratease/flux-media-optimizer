import React from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Typography,
  Stack,
  Skeleton,
  Link,
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import ProcessingAvailabilityChip from '../common/ProcessingAvailabilityChip';
import CapabilityChip from '../common/CapabilityChip';
import { getMissingCapabilityDescriptors } from '../common/capabilityDescriptors';

/**
 * First-activation welcome dialog with local processing summary and optional CDN upsell.
 *
 * @since 4.3.1
 * @param {Object} props Component props.
 * @param {boolean} props.open Whether the dialog is open.
 * @param {Function} props.onClose Close handler.
 * @param {Object|null} props.status System status payload from useSystemStatus.
 * @param {boolean} props.loading Whether status is loading.
 * @param {boolean} props.showUpsell Whether to show the unlicensed CDN upsell.
 * @param {string} props.upsellUrl Buy URL with UTM params.
 * @return {JSX.Element} Welcome dialog.
 */
const WelcomeModal = ({ open, onClose, status, loading, showUpsell, upsellUrl }) => {
  const imageAvailable = Boolean(status?.imageProcessor?.available);
  const videoAvailable = Boolean(status?.videoProcessor?.available);
  const missingImage = getMissingCapabilityDescriptors(status?.imageProcessor, 'image');
  const missingVideo = getMissingCapabilityDescriptors(status?.videoProcessor, 'video');

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="sm"
      fullWidth
      aria-labelledby="flux-media-optimizer-welcome-title"
      data-flux-welcome-modal="1"
    >
      <DialogTitle id="flux-media-optimizer-welcome-title">
        {__('Welcome to Flux Media Optimizer', 'flux-media-optimizer')}
      </DialogTitle>
      <DialogContent>
        <Typography variant="body1" sx={{ mb: 2 }}>
          {__(
            'Your media can be optimized locally on this site. Here is your current image and video processing status.',
            'flux-media-optimizer'
          )}
        </Typography>

        <Stack spacing={1.5} sx={{ mb: showUpsell ? 2 : 0 }}>
          {loading ? (
            <>
              <Skeleton variant="rectangular" width={220} height={32} sx={{ borderRadius: 1 }} />
              <Skeleton variant="rectangular" width={160} height={24} sx={{ borderRadius: 1 }} />
              <Skeleton variant="rectangular" width={220} height={32} sx={{ borderRadius: 1 }} />
              <Skeleton variant="rectangular" width={160} height={24} sx={{ borderRadius: 1 }} />
            </>
          ) : (
            <>
              <Stack
                direction="row"
                spacing={1}
                useFlexGap
                flexWrap="wrap"
                alignItems="center"
                data-flux-welcome-capability-row="image"
              >
                <ProcessingAvailabilityChip
                  available={imageAvailable}
                  type={__('Image Processing', 'flux-media-optimizer')}
                />
                {missingImage.map((descriptor) => (
                  <CapabilityChip
                    key={descriptor.capabilityKey}
                    label={descriptor.label}
                    supported={false}
                    tooltip={descriptor.tooltip}
                    capabilityKey={descriptor.capabilityKey}
                  />
                ))}
              </Stack>
              <Stack
                direction="row"
                spacing={1}
                useFlexGap
                flexWrap="wrap"
                alignItems="center"
                data-flux-welcome-capability-row="video"
              >
                <ProcessingAvailabilityChip
                  available={videoAvailable}
                  type={__('Video Processing', 'flux-media-optimizer')}
                />
                {missingVideo.map((descriptor) => (
                  <CapabilityChip
                    key={descriptor.capabilityKey}
                    label={descriptor.label}
                    supported={false}
                    capabilityKey={descriptor.capabilityKey}
                  />
                ))}
              </Stack>
            </>
          )}
        </Stack>

        {showUpsell && (
          <Typography
            variant="body2"
            color="text.secondary"
            sx={{ mt: 1 }}
            data-flux-welcome-upsell="1"
          >
            {__(
              'Upgrade for offloaded image and video processing plus a global CDN.',
              'flux-media-optimizer'
            )}{' '}
            <Link
              href={upsellUrl}
              target="_blank"
              rel="noopener noreferrer"
              data-flux-welcome-upsell-link="1"
            >
              {__('Learn more', 'flux-media-optimizer')}
            </Link>
          </Typography>
        )}
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} variant="contained" color="primary" data-flux-welcome-dismiss="1">
          {__('Get started', 'flux-media-optimizer')}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default WelcomeModal;
