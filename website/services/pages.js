import { apiRequest } from "./api.js";

/**
 * Fetch homepage data
 * @returns {Promise<Object>} - Homepage data
 */
export const getHomepageData = async () => {
  try {
    const response = await apiRequest("pages/homepage");
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
 * Fetch footer data
 * @returns {Promise<Object>} - Footer data
 */
export const getFooterData = async () => {
  try {
    const response = await apiRequest("/pages/footer");
    return response.data;
  } catch (error) {
    console.error("Error fetching footer data:", error);
    throw error;
  }
};

/**
 * Fetch a global set by handle (e.g. terms_and_privacy, consent)
 * @param {string} handle - Global set handle
 * @returns {Promise<Object>} - Global data (typically includes content key)
 */
export const getGlobalByHandle = async (handle) => {
  try {
    const response = await apiRequest(`/globals/${handle}`);
    return response;
  } catch (error) {
    console.error(`Error fetching global ${handle}:`, error);
    throw error;
  }
};

/**
 * Fetch terms and privacy content
 * @returns {Promise<Object>} - Terms and privacy global (key: content)
 */
export const getTermsAndPrivacyData = async () => {
  return getGlobalByHandle("terms_and_privacy");
};

/**
 * Fetch consent content for registration
 * @returns {Promise<Object>} - Consent global (key: content)
 */
export const getConsentData = async () => {
  return getGlobalByHandle("consent");
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
 * Fetch all regions/branches
 * @returns {Promise<Object>} - Regions data
 */
export const getAllRegions = async () => {
  try {
    const response = await apiRequest("/branches");
    const filteredRegions  =  response?.data?.filter((item) => {
      return item.status === true
    })
    return filteredRegions;
  } catch (error) {
    console.error("Error fetching regions:", error);
    throw error;
  }
};

/**
 * Fetch centers in a specific region
 * @param {string|number} regionId - Region ID
 * @returns {Promise<Object>} - Centers data
 */
export const getRegionCenters = async (regionId) => {
  try {
    const response = await apiRequest(`/branch/${regionId}/centres`);
    return response;
  } catch (error) {
    console.error(`Error fetching centers for region ${regionId}:`, error);
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
    const isFormData = formData instanceof FormData;
    const response = await apiRequest("/add-student", {
      method: 'POST',
      data: formData,
      ...(isFormData && {
        headers: { 'Content-Type': 'multipart/form-data' },
      }),
    });
    return response;
  } catch (error) {
    console.error("Error submitting registration:", error);
    throw error;
  }
};

/**
 * Fetch programmes available at a specific center
 * @param {string|number} centreId - Centre ID
 * @returns {Promise<Object>} - Centre programmes data
 */
export const getCentreProgrammes = async (centreId) => {
  try {
    const response = await apiRequest(`/centre/${centreId}/programmes`);
    return response;
  } catch (error) {
    console.error(`Error fetching programmes for centre ${centreId}:`, error);
    throw error;
  }
};

/**
 * Fetch districts in a specific branch/region
 * @param {string|number} branchId - Branch/Region ID
 * @returns {Promise<Object>} - Districts data
 */
export const getDistrictsByBranch = async (branchId) => {
  try {
    const response = await apiRequest(`/districts-by-branch?branch_id=${branchId}`);
    return response;
  } catch (error) {
    console.error(`Error fetching districts for branch ${branchId}:`, error);
    throw error;
  }
};

/**
 * Fetch centres in a specific district
 * @param {string|number} districtId - District ID
 * @returns {Promise<Object>} - Centres data
 */
export const getCentresByDistrict = async (districtId) => {
  try {
    const response = await apiRequest(`/centres-by-district?district_id=${districtId}`);
    return response;
  } catch (error) {
    console.error(`Error fetching centres for district ${districtId}:`, error);
    throw error;
  }
};

// ──────────────────────────────────────────────
// OTP Verification API
// ──────────────────────────────────────────────

/**
 * Real-time email availability check.
 * Call this (debounced) as the user types their email to provide instant feedback.
 *
 * @param {string} email
 * @returns {Promise<Object>} - { success, available, reason?, message }
 *   reason is one of: "registered" | "otp_active" | null (when available)
 */
export const checkEmailAvailability = async (email) => {
  try {
    const response = await apiRequest(`/otp/check-email?email=${encodeURIComponent(email)}`);
    return response;
  } catch (error) {
    console.error("Error checking email availability:", error);
    throw error;
  }
};

/**
 * Send an OTP code to the user's email (and optionally associate a phone number).
 * @param {{ email: string, phone?: string, form_uuid: string, recaptcha_token?: string }} data
 * @returns {Promise<Object>} - { success, message, expires_in }
 */
export const sendOtp = async (data) => {
  try {
    const response = await apiRequest("/otp/send", {
      method: "POST",
      data,
      // OTP email sending can take 30-90 seconds when the SMTP server is slow
      // (the backend allows up to 120s). The default 15s global timeout would
      // cause a false "timeout" error while the backend is still sending.
      timeout: 90000,
    });
    return response;
  } catch (error) {
    console.error("Error sending OTP:", error);
    throw error;
  }
};

/**
 * Verify the OTP code the user entered.
 * @param {{ email: string, otp: string }} data
 * @returns {Promise<Object>} - { success, message, verified, remaining_attempts }
 */
export const verifyOtp = async (data) => {
  try {
    const response = await apiRequest("/otp/verify", {
      method: "POST",
      data,
    });
    return response;
  } catch (error) {
    console.error("Error verifying OTP:", error);
    throw error;
  }
};

