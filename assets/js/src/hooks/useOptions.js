import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '@flux-media/services/api';

/**
 * React Query hook for fetching plugin options
 */
export const useOptions = () => {
  return useQuery({
    queryKey: ['options'],
    queryFn: () => apiService.getOptions(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
  });
};

/**
 * React Query hook for updating plugin options
 * Supports both single field updates and bulk updates
 */
export const useUpdateOptions = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (data) => {
      // Pass the data directly to the consolidated updateOptions method
      return apiService.updateOptions(data);
    },
    onSuccess: () => {
      // Invalidate and refetch options
      queryClient.invalidateQueries({ queryKey: ['options'] });
    },
    onError: (error) => {
      console.error('Failed to update options:', error);
    },
  });
};
