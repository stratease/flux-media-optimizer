/**
 * API service for Flux Media WordPress plugin using WordPress apiFetch
 */

import apiFetch from '@wordpress/api-fetch';

class ApiService {
  constructor() {
    this.namespace = 'flux-media/v1';
    
    // Configure apiFetch with proper API root
    const apiRoot = window.fluxMediaAdmin?.apiUrl || '/wp-json/';
    apiFetch.use(apiFetch.createRootURLMiddleware(apiRoot));
    
    console.log('API Service initialized with root:', apiRoot);
  }

  /**
   * Make a request using WordPress apiFetch
   * @param {string} endpoint - The API endpoint
   * @param {Object} options - Request options
   * @returns {Promise} - API response
   */
  async request(endpoint, options = {}) {
    const defaultOptions = {
      path: `/${this.namespace}${endpoint}`,
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

    console.log('API Request:', {
      endpoint,
      path: mergedOptions.path,
      method: mergedOptions.method,
      fullPath: `/${this.namespace}${endpoint}`,
      apiRoot: window.fluxMediaAdmin?.apiUrl,
      nonce: window.fluxMediaAdmin?.nonce,
      headers: mergedOptions.headers,
      options: mergedOptions
    });

    try {
      const response = await apiFetch(mergedOptions);
      
      console.log('API Response:', response);
      
      // WordPress apiFetch returns the data directly for successful requests
      return response;
    } catch (error) {
      console.error('API Error:', error);
      console.error('Error details:', {
        message: error.message,
        code: error.code,
        status: error.status,
        data: error.data
      });
      
      // Throw the error for React Query to handle
      throw error;
    }
  }

  // System endpoints
  async getSystemStatus() {
    return this.request('/system/status');
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

  async getRecentConversions(limit = 10) {
    return this.request(`/conversions/recent?limit=${limit}`);
  }

  // Quota endpoints
  async getQuotaProgress() {
    return this.request('/quota/progress');
  }

  async getPlanInfo() {
    return this.request('/quota/plan');
  }

  // Options endpoints
  async getOptions() {
    const response = await this.request('/options');
    // Map backend options to frontend format
    if (response && typeof response === 'object') {
      return this.mapOptionsToFrontend(response);
    }
    return response;
  }

  async updateOptions(options) {
    // Map frontend field names to backend field names
    const mappedOptions = this.mapOptionsToBackend(options);
    
    return this.request('/options', {
      method: 'POST',
      body: JSON.stringify(mappedOptions),
    });
  }

  /**
   * Map frontend option names to backend option names
   * @param {Object} frontendOptions - Options from frontend
   * @returns {Object} Mapped options for backend
   */
  mapOptionsToBackend(frontendOptions) {
    const mapping = {
      autoConvert: 'image_auto_convert',
      quality: 'image_webp_quality',
      webpEnabled: 'image_formats', // This will be handled specially
      avifEnabled: 'image_formats', // This will be handled specially
      hybridApproach: 'hybrid_approach',
      av1Enabled: 'video_formats', // This will be handled specially
      webmEnabled: 'video_formats', // This will be handled specially
      licenseKey: 'license_key',
    };

    const backendOptions = {};

    // Handle simple mappings
    Object.keys(frontendOptions).forEach(key => {
      if (mapping[key] && !['webpEnabled', 'avifEnabled', 'av1Enabled', 'webmEnabled'].includes(key)) {
        backendOptions[mapping[key]] = frontendOptions[key];
      }
    });

    // Handle image formats
    if (frontendOptions.hybridApproach) {
      backendOptions.image_formats = ['webp', 'avif'];
    } else {
      const imageFormats = [];
      if (frontendOptions.webpEnabled) imageFormats.push('webp');
      if (frontendOptions.avifEnabled) imageFormats.push('avif');
      backendOptions.image_formats = imageFormats;
    }

    // Handle video formats
    const videoFormats = [];
    if (frontendOptions.av1Enabled) videoFormats.push('av1');
    if (frontendOptions.webmEnabled) videoFormats.push('webm');
    backendOptions.video_formats = videoFormats;

    return backendOptions;
  }

  /**
   * Map backend option names to frontend option names
   * @param {Object} backendOptions - Options from backend
   * @returns {Object} Mapped options for frontend
   */
  mapOptionsToFrontend(backendOptions) {
    const frontendOptions = {};

    // Simple mappings
    frontendOptions.autoConvert = backendOptions.image_auto_convert ?? true;
    frontendOptions.quality = backendOptions.image_webp_quality ?? 85;
    frontendOptions.hybridApproach = backendOptions.hybrid_approach ?? true;
    frontendOptions.licenseKey = backendOptions.license_key ?? '';

    // Handle image formats
    const imageFormats = backendOptions.image_formats ?? ['webp', 'avif'];
    frontendOptions.webpEnabled = imageFormats.includes('webp');
    frontendOptions.avifEnabled = imageFormats.includes('avif');

    // Handle video formats
    const videoFormats = backendOptions.video_formats ?? ['av1', 'webm'];
    frontendOptions.av1Enabled = videoFormats.includes('av1');
    frontendOptions.webmEnabled = videoFormats.includes('webm');

    return frontendOptions;
  }

  // Conversion operations
  async startConversion(attachmentId, format) {
    return this.request('/conversions/start', {
      method: 'POST',
      body: JSON.stringify({ attachmentId, format }),
    });
  }

  async cancelConversion(jobId) {
    return this.request(`/conversions/cancel/${jobId}`, {
      method: 'POST',
    });
  }

  async bulkConvert(formats) {
    return this.request('/conversions/bulk', {
      method: 'POST',
      body: JSON.stringify({ formats }),
    });
  }

  // File operations
  async deleteConvertedFile(attachmentId, format) {
    return this.request(`/files/delete/${attachmentId}/${format}`, {
      method: 'DELETE',
    });
  }

  // Logs
  async getLogs(level, limit = 100) {
    const params = new URLSearchParams();
    if (level) params.append('level', level);
    params.append('limit', limit.toString());
    
    return this.request(`/logs?${params.toString()}`);
  }

  // Cleanup operations
  async cleanupTempFiles() {
    return this.request('/cleanup/temp-files', {
      method: 'POST',
    });
  }

  async cleanupOldRecords(days = 30) {
    return this.request('/cleanup/old-records', {
      method: 'POST',
      body: JSON.stringify({ days }),
    });
  }
}

// Export singleton instance
export const apiService = new ApiService();
export default apiService;
