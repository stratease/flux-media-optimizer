import React from 'react';
import {
  Typography,
  Box,
  Chip,
  Grid,
  Alert,
  AlertTitle,
  Divider,
} from '@mui/material';
import {
  CheckCircle,
  Error,
} from '@mui/icons-material';
import { __, _x } from '@wordpress/i18n';

/**
 * Dumb component for displaying system status
 */
const SystemStatusCard = ({ status, loading, error }) => {
  // Handle loading state
  if (loading) {
    return (
      <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
        <Box sx={{ mb: 3 }}>
          <Box sx={{ width: '30%', height: 32, bgcolor: 'grey.300', borderRadius: 1, mb: 2 }} />
          <Box sx={{ width: '50%', height: 20, bgcolor: 'grey.300', borderRadius: 1, mb: 3 }} />
        </Box>
        
        <Grid container spacing={2} sx={{ mb: 3 }}>
          <Grid item>
            <Box sx={{ width: 120, height: 40, bgcolor: 'grey.300', borderRadius: 1 }} />
          </Grid>
          <Grid item>
            <Box sx={{ width: 120, height: 40, bgcolor: 'grey.300', borderRadius: 1 }} />
          </Grid>
          <Grid item>
            <Box sx={{ width: 120, height: 40, bgcolor: 'grey.300', borderRadius: 1 }} />
          </Grid>
        </Grid>
        
        <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 2 }}>
          <Box sx={{ width: '100%', height: 60, bgcolor: 'grey.300', borderRadius: 1 }} />
          <Box sx={{ width: '100%', height: 60, bgcolor: 'grey.300', borderRadius: 1 }} />
          <Box sx={{ width: '100%', height: 60, bgcolor: 'grey.300', borderRadius: 1 }} />
        </Box>
      </Box>
    );
  }

  // Handle error state
  if (error) {
    return (
      <Alert severity="error" sx={{ mb: 3 }}>
        {__('Error loading system status:', 'flux-media')} {error?.message || __('Unknown error occurred', 'flux-media')}
      </Alert>
    );
  }

  // Handle no data state
  if (!status) {
    return (
      <Alert severity="warning" sx={{ mb: 3 }}>
        {__('No system status data available', 'flux-media')}
      </Alert>
    );
  }

  const getStatusIcon = (available) => {
    return available ? (
      <CheckCircle color="success" />
    ) : (
      <Error color="error" />
    );
  };

  const getStatusChip = (available, type) => {
    return (
      <Chip
        icon={getStatusIcon(available)}
        label={available ? `${type} ${__('Available', 'flux-media')}` : `${type} ${__('Not Available', 'flux-media')}`}
        color={available ? 'success' : 'error'}
        size="small"
      />
    );
  };

  // Safely access nested properties with fallbacks
  const imageProcessor = status.imageProcessor || {};
  const videoProcessor = status.videoProcessor || {};

  const hasImageSupport = imageProcessor.available && 
    (imageProcessor.webpSupport || imageProcessor.avifSupport);
  
  const hasVideoSupport = videoProcessor.available && 
    (videoProcessor.av1Support || videoProcessor.webmSupport);

  return (
    <Box>
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" gutterBottom>
          System Status
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Current system capabilities and requirements
        </Typography>
      </Box>
      <Divider sx={{ mb: 3 }} />
        <Grid container spacing={3}>
          {/* Image Processing Status */}
          <Grid item xs={12} md={6}>
            <Box>
              <Typography variant="h6" gutterBottom>
                Image Processing
              </Typography>
              <Box sx={{ mb: 2 }}>
                {getStatusChip(imageProcessor.available, (imageProcessor.type || 'Unknown').toUpperCase())}
              </Box>
              
              {imageProcessor.available && (
                <Box sx={{ ml: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Version: {imageProcessor.version || 'Unknown'}
                  </Typography>
                  <Grid container spacing={1} sx={{ mt: 1 }}>
                    <Grid item>
                      <Chip
                        label="WebP"
                        color={imageProcessor.webpSupport ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                    <Grid item>
                      <Chip
                        label="AVIF"
                        color={imageProcessor.avifSupport ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                  </Grid>
                </Box>
              )}
            </Box>
          </Grid>

          {/* Video Processing Status */}
          <Grid item xs={12} md={6}>
            <Box>
              <Typography variant="h6" gutterBottom>
                Video Processing
              </Typography>
              <Box sx={{ mb: 2 }}>
                {getStatusChip(videoProcessor.available, (videoProcessor.type || 'Unknown').toUpperCase())}
              </Box>
              
              {videoProcessor.available && (
                <Box sx={{ ml: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Version: {videoProcessor.version || 'Unknown'}
                  </Typography>
                  <Grid container spacing={1} sx={{ mt: 1 }}>
                    <Grid item>
                      <Chip
                        label="AV1"
                        color={videoProcessor.av1Support ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                    <Grid item>
                      <Chip
                        label="WebM"
                        color={videoProcessor.webmSupport ? 'success' : 'default'}
                        size="small"
                      />
                    </Grid>
                  </Grid>
                </Box>
              )}
            </Box>
          </Grid>

          {/* PHP Configuration */}
          <Grid item xs={12}>
            <Typography variant="h6" gutterBottom>
              PHP Configuration
            </Typography>
            <Grid container spacing={2}>
              <Grid item xs={6} sm={3}>
                <Typography variant="body2" color="text.secondary">
                  PHP Version
                </Typography>
                <Typography variant="body1">
                  {status.phpVersion || 'Unknown'}
                </Typography>
              </Grid>
              <Grid item xs={6} sm={3}>
                <Typography variant="body2" color="text.secondary">
                  Memory Limit
                </Typography>
                <Typography variant="body1">
                  {status.memoryLimit || 'Unknown'}
                </Typography>
              </Grid>
              <Grid item xs={6} sm={3}>
                <Typography variant="body2" color="text.secondary">
                  Max Execution Time
                </Typography>
                <Typography variant="body1">
                  {status.maxExecutionTime || 'Unknown'}s
                </Typography>
              </Grid>
              <Grid item xs={6} sm={3}>
                <Typography variant="body2" color="text.secondary">
                  Upload Max Filesize
                </Typography>
                <Typography variant="body1">
                  {status.uploadMaxFilesize || 'Unknown'}
                </Typography>
              </Grid>
            </Grid>
          </Grid>

          {/* Warnings and Recommendations */}
          {(!hasImageSupport || !hasVideoSupport) && (
            <Grid item xs={12}>
              <Alert severity="warning">
                <AlertTitle>System Requirements Not Met</AlertTitle>
                <Typography variant="body2">
                  {!hasImageSupport && !hasVideoSupport && 
                    'Neither image nor video processing is available. Please install Imagick/GD with WebP/AVIF support and FFmpeg with AV1/WebM support.'}
                  {!hasImageSupport && hasVideoSupport && 
                    'Image processing is not available. Please install Imagick or GD with WebP/AVIF support.'}
                  {hasImageSupport && !hasVideoSupport && 
                    'Video processing is not available. Please install FFmpeg with AV1/WebM support.'}
                </Typography>
              </Alert>
            </Grid>
          )}

          {hasImageSupport && hasVideoSupport && (
            <Grid item xs={12}>
              <Alert severity="success">
                <AlertTitle>System Ready</AlertTitle>
                <Typography variant="body2">
                  All required components are available. Flux Media can optimize both images and videos.
                </Typography>
              </Alert>
            </Grid>
          )}
        </Grid>
    </Box>
  );
};

export default SystemStatusCard;
