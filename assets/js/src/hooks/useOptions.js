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
 */
export const useUpdateOptions = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (options) => apiService.updateOptions(options),
    onSuccess: () => {
      // Invalidate and refetch options
      queryClient.invalidateQueries({ queryKey: ['options'] });
    },
    onError: (error) => {
      console.error('Failed to update options:', error);
    },
  });
};
