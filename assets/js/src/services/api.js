/**
 * API service for Flux Media Optimizer WordPress plugin using WordPress apiFetch
 */

import apiFetch from '@wordpress/api-fetch';

class ApiService {
  constructor() {
    this.namespace = 'flux-media-optimizer/v1';
    
    // Configure apiFetch with proper API root
    const apiRoot = window.fluxMediaAdmin?.apiUrl || '/wp-json/';
    apiFetch.use(apiFetch.createRootURLMiddleware(apiRoot));
  }

  /**
   * Make a request using WordPress apiFetch
   * @param {string} endpoint - The API endpoint (will be prefixed with namespace)
   * @param {Object} options - Request options
   * @returns {Promise} - API response
   */
  async request(endpoint, options = {}) {
    // Prepend namespace if not already included
    let path = endpoint;
    if (!endpoint.startsWith(`/${this.namespace}/`) && !endpoint.startsWith(this.namespace)) {
      // Ensure endpoint starts with /
      const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
      path = `/${this.namespace}${cleanEndpoint}`;
    }
    
    const defaultOptions = {
      path: path,
      method: 'GET',
      headers: {
        'X-WP-Nonce': window.fluxMediaAdmin?.nonce || '',
        'Content-Type': 'application/json',
      },
    };

    const mergedOptions = {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...(options.headers || {}),
      },
    };

    try {
      const response = await apiFetch(mergedOptions);
      
      // Handle the new structured response format
      if (response && typeof response === 'object' && response.success !== undefined) {
        // New format: { success: true, data: {...}, message: "...", timestamp: "..." }
        return response.data;
      }
      
      // Legacy format: return data directly
      return response;
    } catch (error) {
      // Throw the error for React Query to handle
      throw error;
    }
  }

  // System endpoints
  async getSystemStatus() {
    return this.request('/status');
  }

  /**
   * Mark the first-activation welcome modal as viewed.
   *
   * @since 4.3.1
   * @return {Promise} API response data.
   */
  async markWelcomeViewed() {
    return this.request('/welcome/viewed', {
      method: 'POST',
    });
  }

  /**
   * Mark the once-ever review prompt as viewed/consumed.
   *
   * @since 4.3.1
   * @return {Promise} API response data.
   */
  async markReviewPromptViewed() {
    return this.request('/review-prompt/viewed', {
      method: 'POST',
    });
  }

  // Conversion endpoints
  async getConversionStats(filters = {}) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });
    
    const queryString = params.toString();
    const endpoint = queryString ? `/conversions/stats?${queryString}` : '/conversions/stats';
    
    return this.request(endpoint);
  }

  /**
   * Fetch bulk conversion queue statistics.
   *
   * @since 4.4.0
   * @return {Promise<Object>} Bulk stats payload.
   */
  async getBulkStats() {
    return this.request('/bulk/stats');
  }

  // Options endpoints
  async getOptions() {
    return this.request('/options');
  }

  async updateOptions(options) {
    return this.request('/options', {
      method: 'POST',
      body: JSON.stringify({ options }),
    });
  }
}

// Export singleton instance
export const apiService = new ApiService();
export default apiService;
