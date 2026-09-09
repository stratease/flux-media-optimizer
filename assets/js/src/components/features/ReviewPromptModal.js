import React from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Typography,
  Chip,
  Grid,
  IconButton,
} from '@mui/material';
import { Close, SignalCellularAlt } from '@mui/icons-material';
import { __, sprintf } from '@wordpress/i18n';
import { BrandIcon } from '@flux-plugins-common/components';

/**
 * Format byte count as megabytes for review copy.
 *
 * @since 4.3.1
 * @param {number} bytes Byte count.
 * @return {string} Formatted MB string without unit suffix.
 */
const formatMegabytesValue = (bytes) => {
  const value = Number(bytes) || 0;
  if (value <= 0) {
    return '0';
  }

  return (value / 1024 / 1024).toFixed(1);
};

/**
 * Once-ever review ask Dialog with bandwidth savings copy.
 *
 * Header uses shared BrandIcon. Body interpolates a success Chip for savings
 * and friendly solopreneur supporting copy.
 *
 * @since 4.3.1
 * @param {Object} props Component props.
 * @param {boolean} props.open Whether the dialog is open.
 * @param {Function} props.onClose Close handler.
 * @param {number} props.savingsBytes Total savings in bytes for copy.
 * @param {string} props.reviewUrl WordPress.org reviews URL.
 * @param {string} props.supportUrl WordPress.org support forum URL.
 * @return {JSX.Element} Review prompt dialog.
 */
const ReviewPromptModal = ({ open, onClose, savingsBytes, reviewUrl, supportUrl }) => {
  const savingsLabel = sprintf(
    /* translators: %s: megabytes of download bandwidth saved */
    __('%s MB', 'flux-media-optimizer'),
    formatMegabytesValue(savingsBytes)
  );
  const [bodyBefore, bodyAfter] = __(
    /* translators: %s: placeholder for a savings amount chip (e.g. "156.6 MB") */
    'Flux Media Optimizer has already saved about %s of download bandwidth on this site. If it\'s been helpful, would you mind leaving a quick review?',
    'flux-media-optimizer'
  ).split('%s');

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="sm"
      fullWidth
      aria-labelledby="flux-media-optimizer-review-title"
      data-flux-review-prompt-modal="1"
    >
      <DialogTitle component="div">
        <Grid container spacing={2} alignItems="center" wrap="nowrap">
          <Grid item>
            <BrandIcon size={28} />
          </Grid>
          <Grid item xs>
            <Typography variant="h6" component="h2" id="flux-media-optimizer-review-title">
              {__('Enjoying Flux Media Optimizer?', 'flux-media-optimizer')}
            </Typography>
          </Grid>
          <Grid item>
            <IconButton
              aria-label={__('Close', 'flux-media-optimizer')}
              onClick={onClose}
              size="small"
            >
              <Close />
            </IconButton>
          </Grid>
        </Grid>
      </DialogTitle>
      <DialogContent>
        <Typography variant="body1" component="p" gutterBottom>
          {bodyBefore}
          <Chip
            component="span"
            icon={<SignalCellularAlt />}
            label={savingsLabel}
            color="success"
            size="small"
            data-flux-review-savings-chip="1"
          />
          {bodyAfter}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {__(
            'Your feedback really helps solopreneurs like me - thanks!',
            'flux-media-optimizer'
          )}
        </Typography>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} variant="text" color="inherit" data-flux-review-dismiss="1">
          {__('Not now', 'flux-media-optimizer')}
        </Button>
        <Button
          component="a"
          href={supportUrl || undefined}
          target="_blank"
          rel="noopener noreferrer"
          variant="text"
          color="primary"
          data-flux-support-link="1"
          onClick={onClose}
        >
          {__('Need help?', 'flux-media-optimizer')}
        </Button>
        <Button
          component="a"
          href={reviewUrl}
          target="_blank"
          rel="noopener noreferrer"
          variant="contained"
          color="primary"
          data-flux-review-cta="1"
          onClick={onClose}
        >
          {__('Leave a review', 'flux-media-optimizer')}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default ReviewPromptModal;
