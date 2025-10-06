import React from 'react';
import { Box, Skeleton, Typography } from '@mui/material';

/**
 * Generic page skeleton component
 */
const PageSkeleton = ({ title, subtitle, children }) => {
  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4 }}>
        <Skeleton variant="text" width="40%" height={48} sx={{ mb: 1 }} />
        <Skeleton variant="text" width="60%" height={24} />
      </Box>
      
      {/* Page Content */}
      {children}
    </Box>
  );
};

/**
 * Overview page skeleton
 */
export const OverviewPageSkeleton = () => {
  return (
    <PageSkeleton>
      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
        {/* System Status Card */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="30%" height={32} sx={{ mb: 2 }} />
          <Skeleton variant="text" width="50%" height={20} sx={{ mb: 3 }} />
          
          <Box sx={{ display: 'flex', gap: 2, mb: 3 }}>
            <Skeleton variant="rectangular" width={120} height={40} />
            <Skeleton variant="rectangular" width={120} height={40} />
            <Skeleton variant="rectangular" width={120} height={40} />
          </Box>
          
          <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 2 }}>
            <Skeleton variant="rectangular" width="100%" height={60} />
            <Skeleton variant="rectangular" width="100%" height={60} />
            <Skeleton variant="rectangular" width="100%" height={60} />
          </Box>
        </Box>

        {/* Quota Progress Card */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Box>
              <Skeleton variant="text" width="35%" height={32} sx={{ mb: 1 }} />
              <Skeleton variant="text" width="25%" height={20} />
            </Box>
            <Skeleton variant="rectangular" width={100} height={36} />
          </Box>
          
          <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: 3 }}>
            <Box>
              <Skeleton variant="text" width="40%" height={20} sx={{ mb: 1 }} />
              <Skeleton variant="rectangular" width="100%" height={8} sx={{ mb: 1 }} />
              <Skeleton variant="text" width="30%" height={16} />
            </Box>
            <Box>
              <Skeleton variant="text" width="40%" height={20} sx={{ mb: 1 }} />
              <Skeleton variant="rectangular" width="100%" height={8} sx={{ mb: 1 }} />
              <Skeleton variant="text" width="30%" height={16} />
            </Box>
          </Box>
        </Box>

        {/* Conversion Statistics */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="30%" height={32} sx={{ mb: 2 }} />
          <Skeleton variant="text" width="60%" height={20} />
        </Box>
      </Box>
    </PageSkeleton>
  );
};

/**
 * Settings page skeleton
 */
export const SettingsPageSkeleton = () => {
  return (
    <PageSkeleton>
      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
        {/* General Settings */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="25%" height={32} sx={{ mb: 3 }} />
          
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Skeleton variant="rectangular" width={48} height={24} />
              <Skeleton variant="text" width="40%" height={20} />
            </Box>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Skeleton variant="rectangular" width={48} height={24} />
              <Skeleton variant="text" width="35%" height={20} />
            </Box>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Skeleton variant="rectangular" width={48} height={24} />
              <Skeleton variant="text" width="30%" height={20} />
            </Box>
          </Box>
        </Box>

        {/* Video Settings */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="25%" height={32} sx={{ mb: 3 }} />
          
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Skeleton variant="rectangular" width={48} height={24} />
              <Skeleton variant="text" width="35%" height={20} />
            </Box>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Skeleton variant="rectangular" width={48} height={24} />
              <Skeleton variant="text" width="30%" height={20} />
            </Box>
          </Box>
        </Box>

        {/* Quality Settings */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="25%" height={32} sx={{ mb: 2 }} />
          <Skeleton variant="text" width="70%" height={20} sx={{ mb: 2 }} />
          <Skeleton variant="rectangular" width="100%" height={8} />
        </Box>

        {/* License Settings */}
        <Box sx={{ p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
          <Skeleton variant="text" width="25%" height={32} sx={{ mb: 2 }} />
          <Skeleton variant="text" width="80%" height={20} sx={{ mb: 2 }} />
          <Skeleton variant="rectangular" width={400} height={56} />
        </Box>
      </Box>
    </PageSkeleton>
  );
};

export default PageSkeleton;
