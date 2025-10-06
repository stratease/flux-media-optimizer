import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '@flux-media/services/api';

/**
 * React Query hook for starting a conversion
 */
export const useStartConversion = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ attachmentId, format }) => 
      apiService.startConversion(attachmentId, format),
    onSuccess: () => {
      // Invalidate conversion-related queries
      queryClient.invalidateQueries({ queryKey: ['conversions'] });
      queryClient.invalidateQueries({ queryKey: ['quota'] });
    },
    onError: (error) => {
      console.error('Failed to start conversion:', error);
    },
  });
};

/**
 * React Query hook for canceling a conversion
 */
export const useCancelConversion = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (jobId) => apiService.cancelConversion(jobId),
    onSuccess: () => {
      // Invalidate conversion-related queries
      queryClient.invalidateQueries({ queryKey: ['conversions'] });
    },
    onError: (error) => {
      console.error('Failed to cancel conversion:', error);
    },
  });
};

/**
 * React Query hook for bulk conversion
 */
export const useBulkConvert = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (formats) => apiService.bulkConvert(formats),
    onSuccess: () => {
      // Invalidate conversion-related queries
      queryClient.invalidateQueries({ queryKey: ['conversions'] });
      queryClient.invalidateQueries({ queryKey: ['quota'] });
    },
    onError: (error) => {
      console.error('Failed to start bulk conversion:', error);
    },
  });
};

/**
 * React Query hook for deleting converted files
 */
export const useDeleteConvertedFile = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ attachmentId, format }) => 
      apiService.deleteConvertedFile(attachmentId, format),
    onSuccess: () => {
      // Invalidate conversion-related queries
      queryClient.invalidateQueries({ queryKey: ['conversions'] });
    },
    onError: (error) => {
      console.error('Failed to delete converted file:', error);
    },
  });
};
