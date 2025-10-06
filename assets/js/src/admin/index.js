import React from 'react';
import ReactDOM from 'react-dom/client';
import App from '@flux-media/App';

// Mount the React app to the WordPress admin div
const mountApp = () => {
  const container = document.getElementById('flux-media-app');
  if (container) {
    const root = ReactDOM.createRoot(container);
    root.render(<App />);
  }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountApp);
} else {
  mountApp();
}
