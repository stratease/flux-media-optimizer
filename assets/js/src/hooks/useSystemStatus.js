import { useQuery } from '@tanstack/react-query';
import { apiService } from '@flux-media-optimizer/services/api';

/**
 * React Query hook for fetching system status
 */
export const useSystemStatus = () => {
  return useQuery({
    queryKey: ['system', 'status'],
    queryFn: () => apiService.getSystemStatus(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
  });
};