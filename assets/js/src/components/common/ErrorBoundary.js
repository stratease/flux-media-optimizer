import React from 'react';
import { Box, Typography, Button, Alert, AlertTitle } from '@mui/material';

/**
 * Error boundary component for catching React errors
 */
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    this.setState({
      error: error,
      errorInfo: errorInfo
    });
    
    // Log error to console in development
    if (process.env.NODE_ENV === 'development') {
      console.error('ErrorBoundary caught an error:', error, errorInfo);
    }
  }

  handleRetry = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
  };

  render() {
    if (this.state.hasError) {
      return (
        <Box p={3}>
          <Alert severity="error">
            <AlertTitle>Something went wrong</AlertTitle>
            <Typography variant="body2" sx={{ mb: 2 }}>
              An unexpected error occurred. Please try refreshing the page.
            </Typography>
            <Button 
              variant="contained" 
              color="error" 
              onClick={this.handleRetry}
              size="small"
            >
              Try Again
            </Button>
          </Alert>
        </Box>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
