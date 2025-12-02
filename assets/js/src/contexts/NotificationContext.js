import React, { createContext, useContext, useState, useCallback } from 'react';
import { Snackbar, Alert, AlertTitle } from '@mui/material';

/**
 * Global notification context for managing Snackbar notifications
 * 
 * @since 0.1.0
 */
const NotificationContext = createContext();

/**
 * Notification provider component
 */
export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([]);

  const showNotification = useCallback((notification) => {
    const id = Date.now() + Math.random();
    // Default duration: 6 seconds for info/success, 12 seconds for errors/warnings
    const defaultDuration = notification.severity === 'error' || notification.severity === 'warning' ? 12000 : 6000;
    const newNotification = {
      id,
      open: true,
      autoHideDuration: notification.autoHideDuration ?? defaultDuration,
      ...notification,
    };

    setNotifications(prev => [...prev, newNotification]);
  }, []);

  const hideNotification = useCallback((id) => {
    setNotifications(prev => 
      prev.map(notification => 
        notification.id === id 
          ? { ...notification, open: false }
          : notification
      )
    );

    // Remove from state after animation
    setTimeout(() => {
      setNotifications(prev => prev.filter(n => n.id !== id));
    }, 300);
  }, []);

  const showSuccess = useCallback((message, title = null) => {
    showNotification({
      severity: 'success',
      message,
      title,
    });
  }, [showNotification]);

  const showError = useCallback((message, title = 'Error') => {
    showNotification({
      severity: 'error',
      message,
      title,
    });
  }, [showNotification]);

  const showWarning = useCallback((message, title = 'Warning') => {
    showNotification({
      severity: 'warning',
      message,
      title,
    });
  }, [showNotification]);

  const showInfo = useCallback((message, title = 'Info') => {
    showNotification({
      severity: 'info',
      message,
      title,
    });
  }, [showNotification]);

  const value = {
    showNotification,
    hideNotification,
    showSuccess,
    showError,
    showWarning,
    showInfo,
  };

  return (
    <NotificationContext.Provider value={value}>
      {children}
      
      {/* Render all notifications */}
      {notifications.map((notification) => (
        <Snackbar
          key={notification.id}
          open={notification.open}
          autoHideDuration={notification.autoHideDuration}
          onClose={() => hideNotification(notification.id)}
          anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
          sx={{ mb: 1 }}
        >
          <Alert
            onClose={() => hideNotification(notification.id)}
            severity={notification.severity}
            variant="filled"
            sx={{ width: '100%' }}
          >
            {notification.title && <AlertTitle>{notification.title}</AlertTitle>}
            {notification.message}
          </Alert>
        </Snackbar>
      ))}
    </NotificationContext.Provider>
  );
};

/**
 * Hook to use notification context
 */
export const useNotification = () => {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotification must be used within a NotificationProvider');
  }
  return context;
};
