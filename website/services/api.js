import axios from 'axios';
 
const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || "https://onemillioncoders.gikace.org/api";

// Create axios instance with base configuration
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 15000, // 15 seconds timeout
});

// Request interceptor for logging
apiClient.interceptors.request.use(
  (config) => {
    return config;
  },
  (error) => {
    console.error('API Request Error:', error);
    return Promise.reject(error);
  }
);

// Response interceptor for consistent error handling
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    const message = error.response?.data?.message || error.message;
    const status = error.response?.status || 'Unknown';
    console.error(`API Error: ${status} - ${message}`);
    return Promise.reject(error);
  }
);

/**
 * Generic API request function using axios
 * @param {string} endpoint - API endpoint path
 * @param {Object} options - Axios request options
 * @returns {Promise<Object>} - API response data
 */
export const apiRequest = async (endpoint, options = {}) => {
  try {
    const response = await apiClient({
      url: endpoint,
      method: 'GET',
      ...options,
    });

    return response.data;
  } catch (error) {
    console.error('API request failed:', error.message);
    throw error;
  }
};

export default apiRequest; 

/**
 * Fetch page data by slug
 * @param {string} slug - Page slug (e.g., 'pathway', 'about', 'faqs')
 * @returns {Promise<Object>} - Page data
 */
export const getPageData = async (slug) => {
  try {
    const response = await apiRequest(`pages/${slug}`);
    return response.data;
  } catch (error) {
    console.error(`Failed to fetch page data for ${slug}:`, error);
    throw error;
  }
};

/**
 * Fetch branches summary data
 * @returns {Promise<Object>} - Branches summary data
 */
export const fetchBranchesSummary = async () => {
  try {
    const response = await apiRequest('branches/summary');
    return response;
  } catch (error) {
    console.error('Failed to fetch branches summary:', error);
    throw error;
  }
}; 