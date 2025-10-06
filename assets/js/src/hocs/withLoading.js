import React from 'react';
import LoadingSpinner from '@flux-media/components/common/LoadingSpinner';

/**
 * Higher-order component for loading states
 */
const withLoading = (WrappedComponent, loadingMessage = 'Loading...') => {
  return (props) => {
    const { loading, ...otherProps } = props;
    
    if (loading) {
      return <LoadingSpinner message={loadingMessage} />;
    }
    
    return <WrappedComponent {...otherProps} />;
  };
};

export default withLoading;
