import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '@flux-media-optimizer/services/api';

/**
 * React Query hook for getting conversion statistics
 */
export const useConversions = () => {
  return useQuery({
    queryKey: ['conversions', 'stats'],
    queryFn: () => apiService.getConversionStats(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 30 * 1000, // 30 seconds
  });
};

/**
 * React Query hook for getting recent conversions
 */
export const useRecentConversions = (limit = 10) => {
  return useQuery({
    queryKey: ['conversions', 'recent', limit],
    queryFn: () => apiService.getRecentConversions(limit),
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
};

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
  });
};
