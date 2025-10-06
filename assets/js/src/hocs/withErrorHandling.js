import React from 'react';
import { Box, Alert, AlertTitle, Button } from '@mui/material';

/**
 * Higher-order component for error handling
 */
const withErrorHandling = (WrappedComponent, errorMessage = 'An error occurred') => {
  return class extends React.Component {
    constructor(props) {
      super(props);
      this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
      return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
      console.error('Error caught by withErrorHandling:', error, errorInfo);
    }

    handleRetry = () => {
      this.setState({ hasError: false, error: null });
    };

    render() {
      if (this.state.hasError) {
        return (
          <Box p={2}>
            <Alert severity="error">
              <AlertTitle>Error</AlertTitle>
              {errorMessage}
              <Box mt={2}>
                <Button 
                  variant="outlined" 
                  size="small" 
                  onClick={this.handleRetry}
                >
                  Retry
                </Button>
              </Box>
            </Alert>
          </Box>
        );
      }

      return <WrappedComponent {...this.props} />;
    }
  };
};

export default withErrorHandling;
