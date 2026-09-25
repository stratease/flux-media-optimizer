import { useQuery } from '@tanstack/react-query';
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
 * React Query hook for bulk conversion queue statistics.
 *
 * @since 4.4.0
 * @param {boolean} enabled Whether bulk conversion is enabled in settings.
 * @return {Object} TanStack Query result.
 */
export const useBulkStats = (enabled = false) => {
  return useQuery({
    queryKey: ['bulk', 'stats'],
    queryFn: () => apiService.getBulkStats(),
    enabled: Boolean(enabled),
    staleTime: 15 * 1000,
    refetchInterval: enabled ? 30 * 1000 : false,
  });
};
