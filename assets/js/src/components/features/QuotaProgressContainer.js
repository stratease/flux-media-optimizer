import React from 'react';
import { useQuotaProgress } from '@flux-media/hooks/useQuotaProgress';
import QuotaProgressCard from './QuotaProgressCard';

/**
 * Smart container component that fetches quota data and passes it to dumb component
 */
const QuotaProgressContainer = () => {
  const { data: quotaProgress, isLoading, error } = useQuotaProgress();

  const handleUpgrade = () => {
    // TODO: Implement upgrade functionality
    console.log('Upgrade clicked');
  };

  return (
    <QuotaProgressCard
      quota={quotaProgress}
      loading={isLoading}
      error={error}
      onUpgrade={handleUpgrade}
    />
  );
};

export default QuotaProgressContainer;
