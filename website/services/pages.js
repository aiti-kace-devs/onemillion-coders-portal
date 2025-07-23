import { apiRequest } from "./api.js";

/**
 * Fetch homepage data
 * @returns {Promise<Object>} - Homepage data
 */
export const getHomepageData = async () => {
  try {
    const response = await apiRequest("/pages/homepage");
    return response.data;
  } catch (error) {
    console.error("Error fetching homepage data:", error);
    throw error;
  }
};

/**
 * Fetch gallery data
 * @returns {Promise<Object>} - Gallery data
 */
export const getGalleryData = async () => {
  try {
    const response = await apiRequest("/pages/gallery");
    return response.data;
  } catch (error) {
    console.error("Error fetching gallery data:", error);
    throw error;
  }
};

/**
 * Fetch FAQs data
 * @returns {Promise<Object>} - FAQs data
 */
export const getFaqsData = async () => {
  try {
    const response = await apiRequest("/pages/faqs");
    return response.data;
  } catch (error) {
    console.error("Error fetching FAQs data:", error);
    throw error;
  }
};

/**
 * Fetch testimonials data
 * @returns {Promise<Object>} - Testimonials data
 */
export const getTestimonialsData = async () => {
  try {
    const response = await apiRequest("/pages/testimonials");
    return response.data;
  } catch (error) {
    console.error("Error fetching testimonials data:", error);
    throw error;
  }
};

/**
 * Fetch about data
 * @returns {Promise<Object>} - About data
 */
export const getAboutData = async () => {
  try {
    const response = await apiRequest("/pages/about");
    return response.data;
  } catch (error) {
    console.error("Error fetching about data:", error);
    throw error;
  }
};

/**
 * Fetch categories data
 * @returns {Promise<Array>} - Categories data
 */
export const getCategoriesData = async () => {
  try {
    const response = await apiRequest("/categories");
    return response.data; // Note: this endpoint returns data directly, not wrapped in data.data
  } catch (error) {
    console.error("Error fetching categories data:", error);
    throw error;
  }
};

/**
 * Fetch programmes data
 * @param {string} url - Optional URL override for category filtering
 * @returns {Promise<Array>} - Programmes data
 */
export const getProgrammesData = async (url = "/programmes") => {
  try {
    ("Fetching from URL:", url); // Log the URL being called
    const response = await apiRequest(url);
    // ("Full API Response:", response); // Log the full response
    return response.data || []; // API always returns {success, data} structure
  } catch (error) {
    console.error("Error fetching programmes data:", error);
    throw error;
  }
};

/**
 * Fetch single programme data by ID
 * @param {string|number} id - Programme ID
 * @returns {Promise<Object>} - Programme data
 */
export const getProgrammeData = async (id) => {
  try {
    const response = await apiRequest(`/programme/${id}`);
    return response.data; // API returns {success, data} structure
  } catch (error) {
    console.error(`Error fetching programme data for ID ${id}:`, error);
    throw error;
  }
};

/**
 * Fetch page data by slug
 * @param {string} slug - Page slug
 * @returns {Promise<Object>} - Page data
 */
export const getPageData = async (slug) => {
  try {
    const response = await apiRequest(`/pages/${slug}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching page data for ${slug}:`, error);
    throw error;
  }
};

/**
 * Submit course match answers and get recommendations
 * @param {Object} answers - Course match answers
 * @returns {Promise<Array>} - Recommended courses
 */
export const getCourseRecommendations = async (answers) => {
  try {
    const response = await apiRequest("/course-match", {
      method: 'POST',
      data: answers
    });
    return response.data;
  } catch (error) {
    console.error("Error getting course recommendations:", error);
    throw error;
  }
};

/**
 * Fetch programme locations (regions and centres)
 * @param {string|number} programmeId - Programme ID
 * @returns {Promise<Object>} - Programme locations data
 */
export const getProgrammeLocations = async (programmeId) => {
  try {
    const response = await apiRequest(`/programmes/${programmeId}/locations`);
    return response;
  } catch (error) {
    console.error(`Error fetching programme locations for ID ${programmeId}:`, error);
    throw error;
  }
};

/**
 * Fetch registration form schema
 * @returns {Promise<Object>} - Form schema data
 */
export const getRegistrationForm = async () => {
  try {
    const response = await apiRequest("/form");
    return response.data;
  } catch (error) {
    console.error("Error fetching registration form:", error);
    throw error;
  }
};

/**
 * Submit registration form
 * @param {Object} formData - Registration form data
 * @returns {Promise<Object>} - Submission response
 */
export const submitRegistration = async (formData) => {
  try {
    const response = await apiRequest("/register", {
      method: 'POST',
      data: formData
    });
    return response;
  } catch (error) {
    console.error("Error submitting registration:", error);
    throw error;
  }
};
