import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '@flux-media-optimizer/services/api';

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
      // The response will include license_activation if activation occurred
      return apiService.updateOptions(data);
    },
    onSuccess: (responseData) => {
      // Invalidate and refetch options to get updated data including license_activation
      queryClient.invalidateQueries({ queryKey: ['options'] });
      
      // Return the response data so callers can access license_activation
      return responseData;
    },
    onError: (error) => {
      console.error('Failed to update options:', error);
    },
  });
};
