import axios from 'axios';
 
const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || "https://app.omcp.gikace.org/api";

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

/**
 * Check user registration status
 * @param {string} userId - User UUID
 * @returns {Promise<Object>} - User status data
 */
export const checkUserStatus = async (userId) => {
  try {
    const response = await apiRequest(`check-user/${userId}`);
    return response;
  } catch (error) {
    console.error('Failed to check user status:', error);
    throw error;
  }
};

/**
 * Fetch course match questions and options
 * @returns {Promise<Object>} - Course match questions data
 */
export const getCourseMatchQuestions = async (type) => {
  try {
    const params = type ? `?type=${type}` : '';
    const response = await apiRequest(`course-match${params}`);
    return response.data;
  } catch (error) {
    console.error('Failed to fetch course match questions:', error);
    throw error;
  }
};

/**
 * Get course recommendations based on selected options
 * @param {Object} params
 * @param {number[]} params.optionIds - Array of selected option IDs
 * @param {string} params.userId - User UUID
 * @param {number} params.centreId - Centre ID
 * @returns {Promise<Object>} - Course recommendations data
 */
export const getCourseRecommendations = async ({ optionIds, userId, regionId }) => {
  try {
    const response = await apiRequest('recommend/courses', {
      method: 'POST',
      data: {
        option_ids: optionIds,
        userId,
        branch_id: regionId,
      }
    });
    return response.matches || [];
  } catch (error) {
    console.error('Failed to get course recommendations:', error);
    throw error;
  }
};

/**
 * Fetch tiered assessment questions for a user
 * @param {string} userId - User UUID
 * @returns {Promise<Object>} - Assessment questions data
 */
export const fetchAssessmentQuestions = async (userId) => {
  try {
    const response = await apiRequest(`tiered-assessment/fetch?user_id=${userId}`);
    return response;
  } catch (error) {
    console.error('Failed to fetch assessment questions:', error);
    throw error;
  }
};

/**
 * Submit an answer for a tiered assessment question
 * @param {string} userId - User UUID
 * @param {number} questionId - Question ID
 * @param {string} answer - Selected answer
 * @returns {Promise<Object>} - Submission response
 */
export const submitAssessmentAnswer = async (userId, questionId, answer) => {
  try {
    const response = await apiRequest(`tiered-assessment/submit?user_id=${userId}`, {
      method: 'POST',
      data: {
        question_id: questionId,
        answer,
      }
    });
    return response;
  } catch (error) {
    console.error('Failed to submit assessment answer:', error);
    throw error;
  }
}; 