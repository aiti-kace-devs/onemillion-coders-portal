import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || "https://app.omcp.gikace.org/api";

// Create axios instance with base configuration
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
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
export const checkUserStatus = async (userId, token) => {
  try {
    const response = await apiRequest(`check-user/${userId}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
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
export const getCourseMatchQuestions = async (type, token) => {
  try {
    const params = type ? `?type=${type}` : '';
    const response = await apiRequest(`course-match${params}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch course recommendation questions:', error);
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
export const getCourseRecommendations = async ({ optionIds, userId, regionId, centreId, token }) => {
  try {
    const response = await apiRequest('recommend/courses', {
      method: 'POST',
      data: {
        option_ids: optionIds,
        userId,
        branch_id: regionId,
        centre_id: centreId,
      },
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response.matches || [];
  } catch (error) {
    console.error('Failed to get course recommendations:', error);
    throw error;
  }
};

/**
 * Check if a user has previous recommended courses
 * @param {string} userId - User UUID
 * @returns {Promise<Object>} - { success, title, description, matches }
 */
export const checkUserRecommendedCourses = async (userId, token) => {
  try {
    const response = await apiRequest(`check-user-recommended-courses/${userId}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to check user recommended courses:', error);
    throw error;
  }
};

/**
 * Fetch tiered assessment questions for a user
 * @param {string} userId - User UUID
 * @returns {Promise<Object>} - Assessment questions data
 */
export const fetchAssessmentQuestions = async (userId, token) => {
  try {
    const response = await apiRequest(`tiered-assessment/fetch?user_id=${userId}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
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
export const submitAssessmentAnswer = async (userId, questionId, answer, token) => {
  try {
    const response = await apiRequest(`tiered-assessment/submit?user_id=${userId}`, {
      method: 'POST',
      data: {
        question_id: questionId,
        answer,
      },
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to submit assessment answer:', error);
    throw error;
  }
};

/**
 * Record a violation during tiered assessment
 * @param {string} userId - User UUID
 * @param {number} violationCount - Current violation count
 * @param {string} token - Bearer token
 * @returns {Promise<Object>} - Violation recording response
 */
export const recordViolation = async (userId, violationCount, token) => {
  try {
    const response = await apiRequest(`tiered-assessment/record-violation?user_id=${userId}`, {
      method: 'POST',
      data: {
        violation_count: violationCount,
      },
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to record violation:', error);
    throw error;
  }
};

// ──────────────────────────────────────────────
// Booking & Availability APIs
// ──────────────────────────────────────────────

/**
 * Fetch available batches and sessions for a course at a centre
 * @param {number} courseId
 * @param {string} token
 * @returns {Promise<Object>} - { success, centre, course_type, capacity, batches }
 */
export const getAvailableBatches = async (courseId, token) => {
  try {
    const response = await apiRequest(`availability/batches?course_id=${courseId}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to fetch available batches:', error);
    throw error;
  }
};

/**
 * In-person programmes: cohorts + centre course_sessions (optional per-session limits).
 * @param {number} courseId
 * @param {string} token
 */
export const getInPersonAvailableBatches = async (courseId, token) => {
  try {
    const response = await apiRequest(`availability/batches?course_id=${courseId}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to fetch in-person batches:', error);
    throw error;
  }
};

/**
 * Fetch sibling centres offering the same course
 * @param {number} courseId
 * @param {number} centreId
 * @param {string} token
 * @param {number} limit
 * @returns {Promise<Object>} - { success, origin_centre, programme, alternatives }
 */
export const getSiblingCentres = async (courseId, centreId, token, limit = 3) => {
  try {
    const response = await apiRequest(`availability/sibling-centres?course_id=${courseId}&centre_id=${centreId}&limit=${limit}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to fetch sibling centres:', error);
    throw error;
  }
};

/**
 * Fetch sibling courses (recommended + available) for a user
 * @param {string} userId
 * @param {number|null} courseId
 * @param {string} token
 * @param {number} limit
 * @returns {Promise<Object>} - { success, matches, available_courses }
 */
export const getSiblingCourses = async (userId, courseId, token, limit = 3) => {
  try {
    const params = new URLSearchParams({
      userId,
      limit: String(limit),
    });

    if (courseId !== null && courseId !== undefined) {
      params.set('course_id', String(courseId));
    }

    const response = await apiRequest(`availability/sibling-courses?${params.toString()}`, {
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to fetch sibling courses:', error);
    throw error;
  }
};

/**
 * Create a booking (reserve a slot)
 * @param {{ programme_batch_id: number, course_id: number, session_id?: number }} data
 * @param {string} token
 * @param {{ selfPace?: boolean }} [options] — when selfPace, POST /bookings?self_pace=true (study-from-home cohort attachment; server may omit session_id).
 * @returns {Promise<Object>} - Booking confirmation or 409 if full
 */
export const createBooking = async (data, token, options = {}) => {
  try {
    const qs = options.selfPace ? '?self_pace=true' : '';
    const response = await apiRequest(`bookings${qs}`, {
      method: 'POST',
      data,
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    if (error.response?.status === 409) {
      return { conflict: true, ...error.response.data };
    }
    console.error('Failed to create booking:', error);
    throw error;
  }
};

/**
 * Confirm in-person enrollment (separate from online POST /bookings).
 * @param {{ programme_batch_id: number, course_id: number, course_session_id: number }} data
 * @param {string} token
 */
export const submitInPersonEnrollment = async (data, token) => {
  try {
    const response = await apiRequest('in-person-enrollment', {
      method: 'POST',
      data,
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    if (error.response?.status === 409) {
      return { conflict: true, ...error.response.data };
    }
    console.error('Failed to submit in-person enrollment:', error);
    throw error;
  }
};

/**
 * Set learning mode (self-paced or with support)
 * @param {{ userId: string, course_id: number, centre_id?: number }} data
 * @param {boolean} selfPaced - true for self-paced, false for with support
 * @param {string} token
 * @returns {Promise<Object>}
 */
export const setLearningMode = async (data, selfPaced, token) => {
  try {
    const response = await apiRequest(`switch-to-self-paced-or-with-support?self-paced=${selfPaced}&with-support=${!selfPaced}`, {
      method: 'POST',
      data,
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to set learning mode:', error);
    throw error;
  }
};

/**
 * Add a user to the waitlist for a course
 * @param {string} userId
 * @param {number} courseId
 * @param {string} token
 * @returns {Promise<Object>}
 */
export const joinWaitlist = async (userId, courseId, token) => {
  try {
    const response = await apiRequest('waitlist/add', {
      method: 'POST',
      data: { userId, course_id: courseId },
      ...(token && { headers: { Authorization: `Bearer ${token}` } }),
    });
    return response;
  } catch (error) {
    console.error('Failed to join waitlist:', error);
    throw error;
  }
};

/**
 * Submit Ghana Card verification (image + optional PIN)
 * @param {FormData} formData - Contains 'image' (selfie file) and optionally 'pin' (Ghana Card PIN)
 * @param {string} token - Bearer token
 * @returns {Promise<Object>} - { success, message }
 */
export const submitGhanaCardVerification = async (formData, token) => {
  try {
    const response = await apiRequest('ghana-card/verify', {
      method: 'POST',
      data: formData,
      headers: {
        ...(token && { Authorization: `Bearer ${token}` }),
        'Content-Type': 'multipart/form-data',
      },
    });
    return response;
  } catch (error) {
    console.error('Failed to submit Ghana Card verification:', error);
    throw error;
  }
};
