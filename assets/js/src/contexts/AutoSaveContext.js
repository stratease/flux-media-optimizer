import React, { createContext, useContext, useReducer, useCallback } from 'react';
import { Box, CircularProgress, Grid } from '@mui/material';
import { CheckCircle, Error } from '@mui/icons-material';

/**
 * Auto-save context for managing save states across the application
 * 
 * @since 1.0.0
 */
const AutoSaveContext = createContext();

/**
 * Auto-save status reducer
 */
const autoSaveReducer = (state, action) => {
  switch (action.type) {
    case 'SAVE_START':
      return {
        ...state,
        [action.key]: {
          status: 'saving',
          error: null,
          lastSaved: null,
        },
      };
    case 'SAVE_SUCCESS':
      return {
        ...state,
        [action.key]: {
          status: 'saved',
          error: null,
          lastSaved: new Date().toISOString(),
        },
      };
    case 'SAVE_ERROR':
      return {
        ...state,
        [action.key]: {
          status: 'error',
          error: action.error,
          lastSaved: state[action.key]?.lastSaved || null,
        },
      };
    case 'SAVE_RESET':
      return {
        ...state,
        [action.key]: {
          status: 'idle',
          error: null,
          lastSaved: state[action.key]?.lastSaved || null,
        },
      };
    case 'CLEAR_SAVE_STATE':
      const newState = { ...state };
      delete newState[action.key];
      return newState;
    default:
      return state;
  }
};

/**
 * Auto-save provider component
 */
export const AutoSaveProvider = ({ children }) => {
  const [saveStates, dispatch] = useReducer(autoSaveReducer, {});

  const startSave = useCallback((key) => {
    dispatch({ type: 'SAVE_START', key });
  }, []);

  const markSaveSuccess = useCallback((key) => {
    dispatch({ type: 'SAVE_SUCCESS', key });
  }, []);

  const markSaveError = useCallback((key, error) => {
    dispatch({ type: 'SAVE_ERROR', key, error });
  }, []);

  const resetSaveState = useCallback((key) => {
    dispatch({ type: 'SAVE_RESET', key });
  }, []);

  const clearSaveState = useCallback((key) => {
    dispatch({ type: 'CLEAR_SAVE_STATE', key });
  }, []);

  const value = {
    saveStates,
    startSave,
    markSaveSuccess,
    markSaveError,
    resetSaveState,
    clearSaveState,
  };

  return (
    <AutoSaveContext.Provider value={value}>
      {children}
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

/**
 * Auto-save status indicator component
 */
export const AutoSaveIndicator = ({ saveKey, size = 20, sx = {} }) => {
  const { saveStates } = useAutoSave();
  const saveState = saveStates[saveKey];

  if (!saveState || saveState.status === 'idle') {
    return null;
  }

  const getIcon = () => {
    switch (saveState.status) {
      case 'saving':
        return <CircularProgress size={size} sx={{ color: 'primary.main' }} />;
      case 'saved':
        return <CheckCircle sx={{ color: 'success.main', fontSize: size }} />;
      case 'error':
        return <Error sx={{ color: 'error.main', fontSize: size }} />;
      default:
        return null;
    }
  };

  return (
    <Grid 
      container 
      alignItems="center" 
      justifyContent="center"
      sx={{
        minWidth: size,
        minHeight: size,
        ...sx,
      }}
    >
      <Grid item>
        {getIcon()}
      </Grid>
    </Grid>
  );
};

/**
 * Auto-save status text component
 */
export const AutoSaveStatus = ({ saveKey, showLastSaved = true }) => {
  const { saveStates } = useAutoSave();
  const saveState = saveStates[saveKey];

  if (!saveState || saveState.status === 'idle') {
    return null;
  }

  const getStatusText = () => {
    switch (saveState.status) {
      case 'saving':
        return 'Saving...';
      case 'saved':
        return 'Saved';
      case 'error':
        return 'Save failed';
      default:
        return '';
    }
  };

  const getLastSavedText = () => {
    if (!saveState.lastSaved || !showLastSaved) return null;
    
    const lastSaved = new Date(saveState.lastSaved);
    const now = new Date();
    const diffMs = now - lastSaved;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    
    return lastSaved.toLocaleDateString();
  };

  return (
    <Grid container alignItems="center" spacing={1}>
      <Grid item>
        <AutoSaveIndicator saveKey={saveKey} size={16} />
      </Grid>
      <Grid item>
        <Box sx={{ fontSize: '0.875rem', color: 'text.secondary' }}>
          {getStatusText()}
          {saveState.status === 'saved' && getLastSavedText() && (
            <span> • {getLastSavedText()}</span>
          )}
        </Box>
      </Grid>
    </Grid>
  );
};
