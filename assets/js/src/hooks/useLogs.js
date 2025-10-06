import { useQuery } from '@tanstack/react-query';
import { apiService } from '@flux-media/services/api';

/**
 * React Query hook for fetching logs
 */
export const useLogs = (level = null, limit = 100) => {
  return useQuery({
    queryKey: ['logs', level, limit],
    queryFn: () => apiService.getLogs(level, limit),
    staleTime: 30 * 1000, // 30 seconds
    retry: 2,
  });
};
