import React, { createContext, useContext, useCallback, useState } from 'react';
import { CircularProgress, Snackbar, Alert } from '@mui/material';
import { __ } from '@wordpress/i18n';

/**
 * Auto-save context for managing save states across the application
 * 
 * @since 0.1.0
 */
const AutoSaveContext = createContext();


/**
 * Auto-save provider component
 */
export const AutoSaveProvider = ({ children }) => {
  const [snackbar, setSnackbar] = useState({
    open: false,
    message: '',
    severity: 'info',
  });

  const startSave = useCallback((key) => {
    setSnackbar({
      open: true,
      message: __('Saving...', 'flux-media-optimizer'),
      severity: 'info',
      showSpinner: true,
    });
  }, []);

  const markSaveSuccess = useCallback((key) => {
    setSnackbar({
      open: true,
      message: __('Settings saved successfully', 'flux-media-optimizer'),
      severity: 'success',
      showSpinner: false,
    });
  }, []);

  const markSaveError = useCallback((key, error) => {
    setSnackbar({
      open: true,
      message: error || __('Failed to save settings', 'flux-media-optimizer'),
      severity: 'error',
      showSpinner: false,
    });
  }, []);

  const resetSaveState = useCallback((key) => {
    // No-op for simplified version
  }, []);

  const clearSaveState = useCallback((key) => {
    // No-op for simplified version
  }, []);

  const handleSnackbarClose = useCallback((event, reason) => {
    if (reason === 'clickaway') {
      return;
    }
    setSnackbar(prev => ({ ...prev, open: false }));
  }, []);

  const value = {
    startSave,
    markSaveSuccess,
    markSaveError,
    resetSaveState,
    clearSaveState,
  };

  return (
    <AutoSaveContext.Provider value={value}>
      {children}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={snackbar.severity === 'error' ? 12000 : 3000}
        onClose={handleSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        sx={{ mb: 2, mr: 2 }}
      >
        <Alert
          onClose={snackbar.severity !== 'info' ? handleSnackbarClose : undefined}
          severity={snackbar.severity}
          variant="filled"
          sx={{ width: '100%' }}
          icon={snackbar.showSpinner ? <CircularProgress size={20} sx={{ color: 'inherit' }} /> : undefined}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </AutoSaveContext.Provider>
  );
};

/**
 * Hook to use auto-save context
 */
export const useAutoSave = () => {
  const context = useContext(AutoSaveContext);
  if (!context) {
    throw new Error('useAutoSave must be used within an AutoSaveProvider');
  }
  return context;
};

