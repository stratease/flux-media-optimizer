import React, { useState } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  FormControlLabel,
  Checkbox,
  Alert,
  Stack,
  Divider,
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useSubscribeNewsletter } from '@flux-media-optimizer/hooks';

/**
 * SubscribeForm component for newsletter subscription
 * Integrates with external newsletter service
 */
const SubscribeForm = () => {
  // Get localized email directly from WordPress
  const localizedEmail = window.fluxMediaAdmin?.userEmail || '';
  
  const [formData, setFormData] = useState({
    email: localizedEmail,
    privacyAccepted: false,
  });
  const [submitStatus, setSubmitStatus] = useState(null); // 'success', 'error', null

  // Use React Query hooks
  const newsletterMutation = useSubscribeNewsletter();

  const handleInputChange = (field) => (event) => {
    const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
    setFormData(prev => ({
      ...prev,
      [field]: value,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    
    if (!formData.email || !formData.privacyAccepted) {
      setSubmitStatus('error');
      return;
    }

    setSubmitStatus(null);

    newsletterMutation.mutate(formData, {
      onSuccess: () => {
        setSubmitStatus('success');
        setFormData({ email: '', privacyAccepted: false });
      },
      onError: () => {
        setSubmitStatus('error');
      },
    });
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        {__('Stay Updated', 'flux-media-optimizer')}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        {__('Subscribe to our newsletter for updates on Flux Media Optimizer features, tips, and announcements.', 'flux-media-optimizer')}
      </Typography>

      <Box component="form" onSubmit={handleSubmit}>
        <Stack spacing={2}>
          <TextField
            fullWidth
            type="email"
            label={__('Email Address', 'flux-media-optimizer')}
            placeholder={__('Enter your email address', 'flux-media-optimizer')}
            value={formData.email}
            onChange={handleInputChange('email')}
            required
            disabled={newsletterMutation.isPending}
            variant="outlined"
            size="small"
            sx={{ maxWidth: 400 }}
          />

          <FormControlLabel
            control={
              <Checkbox
                checked={formData.privacyAccepted}
                onChange={handleInputChange('privacyAccepted')}
                disabled={newsletterMutation.isPending}
                required
                size="small"
              />
            }
            label={
              <Typography component="span" variant="body2">
                {__('I accept the', 'flux-media-optimizer')}{' '}
                <a 
                  href="https://fluxplugins.com/privacy-policy" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  style={{ color: 'inherit', textDecoration: 'underline' }}
                >
                  {__('privacy policy', 'flux-media-optimizer')}
                </a>
              </Typography>
            } 
          />

          <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
            <Button
              type="submit"
              variant="contained"
              disabled={newsletterMutation.isPending || !formData.email || !formData.privacyAccepted}
              sx={{ minWidth: 120 }}
            >
              {newsletterMutation.isPending ? __('Subscribing...', 'flux-media-optimizer') : __('Subscribe', 'flux-media-optimizer')}
            </Button>
          </Box>

          {submitStatus === 'success' && (
            <Alert severity="success" sx={{ mt: 2 }}>
              {__('Thank you for subscribing! You will receive updates about Flux Media Optimizer.', 'flux-media-optimizer')}
            </Alert>
          )}

          {submitStatus === 'error' && (
            <Alert severity="error" sx={{ mt: 2 }}>
              {__('There was an error subscribing. Please check your email address and try again.', 'flux-media-optimizer')}
            </Alert>
          )}
        </Stack>
      </Box>

      <Divider sx={{ my: 2 }} />
      
      <Typography variant="caption" color="text.secondary">
        {__('By subscribing, you agree to receive emails from Flux Media Optimizer. You can unsubscribe at any time.', 'flux-media-optimizer')}
      </Typography>
    </Box>
  );
};

export default SubscribeForm;
