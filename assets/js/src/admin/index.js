import React from 'react';
import ReactDOM from 'react-dom/client';
import App from '@flux-media-optimizer/App';

// Mount the React app to the WordPress admin div
const mountApp = () => {
  const container = document.getElementById('flux-media-optimizer-app');
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
