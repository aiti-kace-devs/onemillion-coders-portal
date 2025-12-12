/**
 * Course Image Utility
 * Provides consistent static images for courses
 * TEMPORARY: This is a temporary measure while API images are commented out
 */

// List of available course images in public/images/courses/
const COURSE_IMAGES = [
  '/images/courses/certified-data-protection-expert.jpg',
  '/images/courses/cybersecuirty-officer.jpg',
  '/images/courses/cybersecurity-professional.JPG',
  '/images/courses/data-analyst-associate.JPG',
  '/images/courses/data-analyst.jpg',
  '/images/courses/data-protection-manager.jpg',
  '/images/courses/data-protection-practioner.jpg',
  '/images/courses/dpo.JPG',
  '/images/courses/network.jpg',
];

/**
 * Get a consistent static image for a course based on its ID
 * Uses modulo operation to ensure the same course always gets the same image
 * @param {number|string} courseId - The course ID
 * @returns {string} - Path to the static image
 */
export function getCourseImage(courseId) {
  // Convert courseId to a number for consistent indexing
  const id = typeof courseId === 'string' ? parseInt(courseId, 10) : courseId;

  // Use modulo to get a consistent index based on the course ID
  // This ensures the same course always gets the same image
  const index = (id || 0) % COURSE_IMAGES.length;

  return COURSE_IMAGES[index];
}

/**
 * Default fallback image for courses
 */
export const DEFAULT_COURSE_IMAGE = '/images/courses/data-protection-manager.jpg';
