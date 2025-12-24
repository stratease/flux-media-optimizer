import { useQuery } from '@tanstack/react-query';
import { apiService } from '@flux-media-optimizer/services/api';

/**
 * React Query hook for fetching conversion statistics
 */
export const useConversionStats = (filters = {}) => {
  return useQuery({
    queryKey: ['conversions', 'stats', filters],
    queryFn: () => apiService.getConversionStats(filters),
    staleTime: 2 * 60 * 1000, // 2 minutes
    retry: 2,
  });
};

/**
 * React Query hook for fetching recent conversions
 */
export const useRecentConversions = (limit = 10) => {
  return useQuery({
    queryKey: ['conversions', 'recent', limit],
    queryFn: () => apiService.getRecentConversions(limit),
    staleTime: 1 * 60 * 1000, // 1 minute
    retry: 2,
  });
};