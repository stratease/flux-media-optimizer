import React from 'react';
import { useSystemStatus } from '@flux-media/hooks/useSystemStatus';
import SystemStatusCard from './SystemStatusCard';

/**
 * Smart container component that fetches data and passes it to dumb component
 */
const SystemStatusContainer = () => {
  const { data: systemStatus, isLoading, error } = useSystemStatus();

  return (
    <SystemStatusCard
      status={systemStatus}
      loading={isLoading}
      error={error}
    />
  );
};

export default SystemStatusContainer;
