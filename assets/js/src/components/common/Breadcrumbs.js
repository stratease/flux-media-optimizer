import React from 'react';
import { Link as RouterLink, useLocation } from 'react-router-dom';
import { Breadcrumbs as MuiBreadcrumbs, Link, Typography, Box, Grid } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { Home, Settings, Dashboard } from '@mui/icons-material';

/**
 * Breadcrumb navigation component using React Router Link
 */
const Breadcrumbs = () => {
  const location = useLocation();

  const getBreadcrumbItems = () => {
    const items = [
      {
        label: __('Flux Media', 'flux-media'),
        path: '/overview',
        icon: <Home fontSize="small" />,
      },
    ];

    switch (location.pathname) {
      case '/settings':
        items.push({
          label: __('Settings', 'flux-media'),
          path: '/settings',
          icon: <Settings fontSize="small" />,
        });
        break;
      case '/overview':
      default:
        items.push({
          label: __('Overview', 'flux-media'),
          path: '/overview',
          icon: <Dashboard fontSize="small" />,
        });
        break;
    }

    return items;
  };

  const breadcrumbItems = getBreadcrumbItems();

  return (
    <Box sx={{ mb: 2 }}>
      <MuiBreadcrumbs aria-label={__('Breadcrumb navigation', 'flux-media')}>
        {breadcrumbItems.map((item, index) => {
          const isLast = index === breadcrumbItems.length - 1;
          
          if (isLast) {
            return (
            <Grid container alignItems="center" spacing={0.5} key={item.path}>
              <Grid item>
                <Typography color="text.primary">
                  {item.icon}
                </Typography>
              </Grid>
              <Grid item>
                <Typography color="text.primary">
                  {item.label}
                </Typography>
              </Grid>
            </Grid>
            );
          }

          return (
            <Link
              key={item.path}
              component={RouterLink}
              to={item.path}
              color="inherit"
              sx={{ 
                textDecoration: 'none',
                '&:hover': {
                  textDecoration: 'underline',
                },
              }}
            >
              <Grid container alignItems="center" spacing={0.5}>
                <Grid item>
                  {item.icon}
                </Grid>
                <Grid item>
                  {item.label}
                </Grid>
              </Grid>
            </Link>
          );
        })}
      </MuiBreadcrumbs>
    </Box>
  );
};

export default Breadcrumbs;
