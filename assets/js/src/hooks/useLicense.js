import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '@flux-media-optimizer/services/api';

/**
 * React Query hook for fetching license information
 */
export const useLicense = () => {
  return useQuery({
    queryKey: ['license'],
    queryFn: () => apiService.getLicense(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
  });
};

/**
 * React Query hook for activating a license key
 */
export const useActivateLicense = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (licenseKey) => {
      return apiService.activateLicense(licenseKey);
    },
    onSuccess: (data) => {
      // Update license cache with response data
      queryClient.setQueryData(['license'], data);
      // Also invalidate to ensure fresh data
      queryClient.invalidateQueries({ queryKey: ['license'] });
    },
    onError: (error) => {
      // Mark license as invalid on error
      const currentLicenseData = queryClient.getQueryData(['license']);
      if (currentLicenseData) {
        queryClient.setQueryData(['license'], {
          ...currentLicenseData,
          license_is_valid: false,
          license_last_valid_date: null,
        });
      }
    },
  });
};

/**
 * React Query hook for validating the current license key
 */
export const useValidateLicense = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: () => {
      return apiService.validateLicense();
    },
    onSuccess: (data) => {
      // Update license cache with response data
      queryClient.setQueryData(['license'], data);
      // Also invalidate to ensure fresh data
      queryClient.invalidateQueries({ queryKey: ['license'] });
    },
    onError: (error) => {
      // Mark license as invalid on error
      const currentLicenseData = queryClient.getQueryData(['license']);
      if (currentLicenseData) {
        queryClient.setQueryData(['license'], {
          ...currentLicenseData,
          license_is_valid: false,
          license_last_valid_date: null,
        });
      }
    },
  });
};

