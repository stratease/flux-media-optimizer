import React from 'react';
import { Grid, Typography, Paper, Box } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { SystemStatusCard, QuotaProgressCard } from '@flux-media/components';
import { useSystemStatus } from '@flux-media/hooks/useSystemStatus';
import { useQuotaProgress } from '@flux-media/hooks/useQuotaProgress';
import { useConversions } from '@flux-media/hooks/useConversions';

/**
 * Overview page component showing system status, quota, and conversion statistics.
 *
 * @since 0.1.0
 */
const OverviewPage = () => {
  const { data: systemStatus, isLoading: systemLoading } = useSystemStatus();
  const { data: quotaData, isLoading: quotaLoading } = useQuotaProgress();
  const { data: conversionStats, isLoading: conversionLoading } = useConversions();

  /**
   * Format bytes into human readable format.
   *
   * @since 0.1.0
   * @param {number} bytes Number of bytes.
   * @return {string} Formatted string.
   */
  const formatBytes = (bytes) => {
    if (bytes <= 0) return '0 B';
    
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const bytesValue = Math.max(bytes, 0);
    const pow = Math.floor((bytesValue ? Math.log(bytesValue) : 0) / Math.log(1024));
    const powValue = Math.min(pow, units.length - 1);
    
    const bytesFormatted = bytesValue / Math.pow(1024, powValue);
    return Math.round(bytesFormatted * 10) / 10 + ' ' + units[powValue];
  };

  /**
   * Get savings statistics display.
   *
   * @since 0.1.0
   * @return {JSX.Element|null} Savings statistics component.
   */
  const getSavingsStats = () => {
    if (conversionLoading || !conversionStats) {
      return null;
    }

    const { total_savings_bytes, total_savings_percentage, by_type } = conversionStats;

    return (
      <Paper elevation={1} sx={{ p: 3, mb: 3 }}>
        <Typography variant="h5" component="h2" gutterBottom>
          {__('Conversion Savings', 'flux-media')}
        </Typography>
        
        <Grid container spacing={3}>
          <Grid item xs={12} md={4}>
            <Box textAlign="center">
              <Typography variant="h3" color="primary" component="div">
                {formatBytes(total_savings_bytes || 0)}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {__('Total Space Saved', 'flux-media')}
              </Typography>
            </Box>
          </Grid>
          
          <Grid item xs={12} md={4}>
            <Box textAlign="center">
              <Typography variant="h3" color="success.main" component="div">
                {total_savings_percentage || 0}%
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {__('Average Reduction', 'flux-media')}
              </Typography>
            </Box>
          </Grid>
          
          <Grid item xs={12} md={4}>
            <Box textAlign="center">
              <Typography variant="h3" color="info.main" component="div">
                {conversionStats.total_conversions || 0}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {__('Total Conversions', 'flux-media')}
              </Typography>
            </Box>
          </Grid>
        </Grid>

        {by_type && Object.keys(by_type).length > 0 && (
          <Box sx={{ mt: 3 }}>
            <Typography variant="h6" gutterBottom>
              {__('Savings by Format', 'flux-media')}
            </Typography>
            <Grid container spacing={2}>
              {Object.entries(by_type).map(([format, stats]) => (
                <Grid item xs={12} sm={6} md={3} key={format}>
                  <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                    <Typography variant="h6" component="div" sx={{ textTransform: 'uppercase' }}>
                      {format}
                    </Typography>
                    <Typography variant="h4" color="primary" component="div">
                      {formatBytes(stats.total_savings_bytes || 0)}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      {stats.savings_percentage || 0}% {__('reduction', 'flux-media')}
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      {stats.count || 0} {__('files', 'flux-media')}
                    </Typography>
                  </Paper>
                </Grid>
              ))}
            </Grid>
          </Box>
        )}
      </Paper>
    );
  };

  return (
    <Box>
      <Typography variant="h4" component="h1" gutterBottom>
        {__('Overview', 'flux-media')}
      </Typography>
      
      <Typography variant="body1" color="text.secondary" paragraph>
        {__('Monitor your media optimization performance and system status.', 'flux-media')}
      </Typography>

      <Grid container spacing={3}>
        <Grid item xs={12} md={6}>
          <SystemStatusCard 
            status={systemStatus} 
            loading={systemLoading}
          />
        </Grid>
        
        {/* Temporarily hidden quota section */}
        {/* <Grid item xs={12} md={6}>
          <QuotaProgressCard 
            quota={quotaData} 
            loading={quotaLoading}
          />
        </Grid> */}
      </Grid>

      {getSavingsStats()}
    </Box>
  );
};

export default OverviewPage;
