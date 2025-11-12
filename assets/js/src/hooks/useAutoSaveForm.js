import { useCallback, useEffect, useRef } from 'react';
import { useAutoSave } from '@flux-media-optimizer/contexts/AutoSaveContext';
import { useUpdateOptions } from './useOptions';

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
  const updateOptionsMutation = useUpdateOptions();
  const timeoutRef = useRef(null);
  const lastSavedDataRef = useRef(JSON.stringify(initialData));

  // Default save function using React Query mutation for options
  const defaultSaveFunction = useCallback(async (options) => {
    return updateOptionsMutation.mutateAsync(options);
  }, [updateOptionsMutation]);

  const saveFn = saveFunction || defaultSaveFunction;

  // Auto-save function
  const autoSave = useCallback(async (options) => {
    try {
      startSave(saveKey);
      
      const response = await saveFn(options);
      
      // The API service returns the data directly, so if we get a response object
      // (not an error), it means the save was successful
      if (response && typeof response === 'object') {
        markSaveSuccess(saveKey);
        lastSavedDataRef.current = JSON.stringify(options);
      } else {
        markSaveError(saveKey, 'Save failed - invalid response');
      }
    } catch (error) {
      console.error('Auto-save error:', error);
      markSaveError(saveKey, error.message || 'An unexpected error occurred');
    }
  }, [saveKey, saveFn, startSave, markSaveSuccess, markSaveError]);

  // Debounced save function
  const debouncedSave = useCallback((options) => {
    // Clear existing timeout
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    // Check if options have actually changed
    const currentOptionsString = JSON.stringify(options);
    if (currentOptionsString === lastSavedDataRef.current) {
      return; // No changes, don't save
    }

    // Set new timeout
    timeoutRef.current = setTimeout(() => {
      autoSave(options);
    }, debounceMs);
  }, [autoSave, debounceMs]);

  // Manual save function (immediate)
  const manualSave = useCallback(async (options) => {
    // Clear any pending debounced save
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    await autoSave(options);
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
