import { useQuery } from '@tanstack/react-query';
import { apiService } from '@flux-media/services/api';

/**
 * React Query hook for fetching quota progress
 */
export const useQuotaProgress = () => {
  return useQuery({
    queryKey: ['quota', 'progress'],
    queryFn: () => apiService.getQuotaProgress(),
    staleTime: 1 * 60 * 1000, // 1 minute
    retry: 2,
  });
};

/**
 * React Query hook for fetching plan information
 */
export const usePlanInfo = () => {
  return useQuery({
    queryKey: ['quota', 'plan'],
    queryFn: () => apiService.getPlanInfo(),
    staleTime: 10 * 60 * 1000, // 10 minutes
    retry: 2,
  });
};