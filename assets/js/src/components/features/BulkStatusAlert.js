import React, { useEffect, useState } from 'react';
import { Alert, Box, Chip, CircularProgress, Skeleton, Stack, Typography } from '@mui/material';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Short chip label for a tracked bulk queue state only.
 *
 * @since 4.4.0
 * @param {string} state Bulk stats state from BulkStatsService.
 * @return {string} Empty string when state is not a tracked value.
 */
const stateLabel = (state) => {
  switch (state) {
    case 'off':
      return __('Off', 'flux-media-optimizer');
    case 'idle':
      return __('Idle', 'flux-media-optimizer');
    case 'queued':
      return __('Queued', 'flux-media-optimizer');
    case 'working':
      return __('Working', 'flux-media-optimizer');
    case 'complete':
      return __('Complete', 'flux-media-optimizer');
    case 'stuck':
      return __('Stuck', 'flux-media-optimizer');
    default:
      return '';
  }
};

/**
 * Chip color for a tracked bulk queue state.
 *
 * @since 4.4.0
 * @param {string} state Bulk stats state from BulkStatsService.
 * @return {'default'|'success'|'warning'|'error'|'info'}
 */
const stateColor = (state) => {
  switch (state) {
    case 'complete':
      return 'success';
    case 'working':
    case 'queued':
      return 'info';
    case 'stuck':
      return 'error';
    case 'idle':
      return 'warning';
    case 'off':
    default:
      return 'default';
  }
};

/**
 * Format remaining time until next batch using browser clock vs Action Scheduler Unix time.
 *
 * AS stores/schedules Unix epoch seconds; Date.now() is the same epoch in the viewer's local timezone.
 *
 * @since 4.4.0
 * @param {number|null} unixSeconds Next discovery/batch Unix timestamp.
 * @param {number} nowMs Browser Date.now() value.
 * @return {string|null} Relative countdown string, or null when overdue/missing (show spinner).
 */
export const formatNextBatchCountdown = (unixSeconds, nowMs) => {
  if (unixSeconds === null || unixSeconds === undefined || unixSeconds === '') {
    return null;
  }

  const unix = Number(unixSeconds);
  if (!Number.isFinite(unix) || unix <= 0) {
    return null;
  }

  const remainingMs = unix * 1000 - nowMs;
  if (remainingMs <= 0) {
    return null;
  }

  let sec = Math.ceil(remainingMs / 1000);
  const days = Math.floor(sec / 86400);
  sec %= 86400;
  const hours = Math.floor(sec / 3600);
  sec %= 3600;
  const minutes = Math.floor(sec / 60);
  const seconds = sec % 60;

  const parts = [];
  if (days > 0) {
    parts.push(`${days}d`);
  }
  if (hours > 0 || days > 0) {
    parts.push(`${hours}h`);
  }
  if (minutes > 0 || hours > 0 || days > 0) {
    parts.push(`${minutes}m`);
  }
  parts.push(`${seconds}s`);

  return sprintf(
    /* translators: %s: relative duration such as "19m 42s" */
    __('in %s', 'flux-media-optimizer'),
    parts.join(' ')
  );
};

/**
 * Shared MUI Alert for bulk conversion progress (Settings + Overview).
 *
 * @since 4.4.0
 * @param {Object} props Component props.
 * @param {Object|null} props.stats Bulk stats payload.
 * @param {boolean} props.loading Whether stats are loading.
 * @return {JSX.Element|null}
 */
const BulkStatusAlert = ({ stats, loading }) => {
  const [nowMs, setNowMs] = useState(() => Date.now());

  useEffect(() => {
    const id = window.setInterval(() => {
      setNowMs(Date.now());
    }, 1000);
    return () => window.clearInterval(id);
  }, []);

  if (loading && !stats) {
    return (
      <Box data-flux-bulk-status="loading" sx={{ mt: 1 }}>
        <Skeleton variant="rounded" height={56} />
      </Box>
    );
  }

  if (!stats || !stats.enabled) {
    return null;
  }

  const label = stateLabel(stats.state);
  const countdown = formatNextBatchCountdown(stats.next_discovery_at, nowMs);
  const nextBatchMode = countdown ? 'countdown' : 'waiting';

  return (
    <Box
      data-flux-bulk-status={stats.state || ''}
      data-flux-bulk-remaining={String(stats.eligible_remaining ?? 0)}
      data-flux-bulk-pending={String(stats.pending_actions ?? 0)}
      data-flux-bulk-next-batch={nextBatchMode}
      sx={{ mt: 1 }}
    >
      <Alert severity={stats.state === 'stuck' ? 'error' : 'info'} sx={{ py: 0.75 }}>
        <Stack spacing={0.5}>
          <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap" useFlexGap>
            {label ? (
              <Chip size="small" color={stateColor(stats.state)} label={label} />
            ) : null}
            <Typography variant="body2" component="span">
              {sprintf(
                /* translators: 1: remaining eligible count, 2: pending Action Scheduler convert actions */
                __('Remaining: %1$d · Queued: %2$d', 'flux-media-optimizer'),
                Number(stats.eligible_remaining ?? 0),
                Number(stats.pending_actions ?? 0)
              )}
            </Typography>
          </Stack>
          <Stack direction="row" spacing={1} alignItems="center">
            <Typography variant="body2" component="span">
              {__('Next batch:', 'flux-media-optimizer')}
            </Typography>
            {countdown ? (
              <Typography variant="body2" component="span">
                {countdown}
              </Typography>
            ) : (
              <CircularProgress size={14} thickness={5} aria-label={__('Waiting for next batch', 'flux-media-optimizer')} />
            )}
          </Stack>
        </Stack>
      </Alert>
    </Box>
  );
};

export default BulkStatusAlert;
