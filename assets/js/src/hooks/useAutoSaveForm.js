import { useCallback, useEffect, useRef } from 'react';
import { useAutoSave } from '@flux-media/contexts/AutoSaveContext';
import { apiService } from '@flux-media/services/api';

/**
 * Custom hook for auto-save form functionality
 * 
 * @param {string} saveKey - Unique key for this form's save state
 * @param {Object} initialData - Initial form data
 * @param {Function} saveFunction - Function to call for saving (optional, defaults to API service)
 * @param {number} debounceMs - Debounce delay in milliseconds (default: 1000)
 * @returns {Object} Auto-save form utilities
 */
export const useAutoSaveForm = (saveKey, initialData = {}, saveFunction = null, debounceMs = 1000) => {
  const { startSave, markSaveSuccess, markSaveError, resetSaveState } = useAutoSave();
  const timeoutRef = useRef(null);
  const lastSavedDataRef = useRef(JSON.stringify(initialData));

  // Default save function using API service
  const defaultSaveFunction = useCallback(async (data) => {
    return apiService.updateOptions(data);
  }, []);

  const saveFn = saveFunction || defaultSaveFunction;

  // Auto-save function
  const autoSave = useCallback(async (data) => {
    try {
      startSave(saveKey);
      
      const response = await saveFn(data);
      
      if (response.success) {
        markSaveSuccess(saveKey);
        lastSavedDataRef.current = JSON.stringify(data);
      } else {
        markSaveError(saveKey, response.message || 'Save failed');
      }
    } catch (error) {
      console.error('Auto-save error:', error);
      markSaveError(saveKey, error.message || 'An unexpected error occurred');
    }
  }, [saveKey, saveFn, startSave, markSaveSuccess, markSaveError]);

  // Debounced save function
  const debouncedSave = useCallback((data) => {
    // Clear existing timeout
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    // Check if data has actually changed
    const currentDataString = JSON.stringify(data);
    if (currentDataString === lastSavedDataRef.current) {
      return; // No changes, don't save
    }

    // Set new timeout
    timeoutRef.current = setTimeout(() => {
      autoSave(data);
    }, debounceMs);
  }, [autoSave, debounceMs]);

  // Manual save function (immediate)
  const manualSave = useCallback(async (data) => {
    // Clear any pending debounced save
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    await autoSave(data);
  }, [autoSave]);

  // Reset save state
  const resetSave = useCallback(() => {
    resetSaveState(saveKey);
    lastSavedDataRef.current = JSON.stringify(initialData);
  }, [saveKey, resetSaveState, initialData]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  return {
    debouncedSave,
    manualSave,
    resetSave,
  };
};
