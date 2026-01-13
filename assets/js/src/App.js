import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Box, Tabs, Tab } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { ErrorBoundary } from '@flux-media-optimizer/components';
import { FluxAppProvider, PageLayout } from '@flux-plugins-common/components';
import OverviewPage from '@flux-media-optimizer/pages/OverviewPage';
import SettingsPage from '@flux-media-optimizer/pages/SettingsPage';
import { AutoSaveProvider } from '@flux-media-optimizer/contexts/AutoSaveContext';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});


/**
 * Navigation component with tabs using React Router
 */
const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const getTabValue = (pathname) => {
    switch (pathname) {
      case '/overview':
        return 0;
      case '/settings':
        return 1;
      default:
        return 0;
    }
  };

  const handleTabChange = (event, newValue) => {
    const paths = ['/overview', '/settings'];
    navigate(paths[newValue]);
  };

  return (
    <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
      <Tabs
        value={getTabValue(location.pathname)}
        onChange={handleTabChange}
        aria-label={__('Flux Media Optimizer navigation tabs', 'flux-media-optimizer')}
        textColor="primary"
        indicatorColor="primary"
      >
        <Tab label={__('Overview', 'flux-media-optimizer')} />
        <Tab label={__('Settings', 'flux-media-optimizer')} />
      </Tabs>
    </Box>
  );
};

/**
 * Main App component with React Router
 */
const App = () => {
  // Handle initial route from WordPress admin menu
  React.useEffect(() => {
    const container = document.getElementById('flux-media-optimizer-app');
    const initialHash = container?.dataset.initialHash;
    
    if (initialHash) {
      const hash = initialHash.startsWith('#') ? initialHash.slice(1) : initialHash;
      // Set the initial hash if it's different from current
      if (window.location.hash !== `#${hash}`) {
        window.location.hash = hash;
      }
    }
  }, []);

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <FluxAppProvider>
          <AutoSaveProvider>
            <Router>
              <PageLayout title={__('Flux Media Optimizer', 'flux-media-optimizer')} maxWidth="xl">
                <Navigation />
                <Routes>
                  <Route path="/overview" element={<OverviewPage />} />
                  <Route path="/settings" element={<SettingsPage />} />
                  <Route path="/" element={<Navigate to="/overview" replace />} />
                </Routes>
              </PageLayout>
            </Router>
          </AutoSaveProvider>
        </FluxAppProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
};

export default App;
