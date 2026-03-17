"use client";

import React, { useState, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, useSearchParams } from "next/navigation";
import Image from "next/image";
import {
  FiMapPin,
  FiChevronRight,
  FiLoader,
  FiArrowLeft,
  FiClock,
  FiCheckCircle,
  FiUser,
  FiTarget,
  FiTrendingUp,
  FiAward,
  FiMap,
  FiAlertCircle,
  FiStar,
  FiSearch,
  FiX,
  FiGlobe,
} from "react-icons/fi";
import {
  getAllRegions,
  getDistrictsByBranch,
  getCentresByDistrict,
  confirmCourse,
} from "../../../services/pages";
import {
  checkUserStatus,
  getCourseMatchQuestions,
  getCourseRecommendations,
  checkUserRecommendedCourses,
} from "../../../services/api";
import Button from "../../../components/Button";

export default function CoursesPage({ params }) {
  const { id } = React.use(params);
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get("token");

  // User verification state
  const [userStatus, setUserStatus] = useState(null);
  const [verifying, setVerifying] = useState(true);
  const [verificationError, setVerificationError] = useState(null);

  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [imageErrors, setImageErrors] = useState({});

  // Location state
  const [allRegions, setAllRegions] = useState(null);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [availableDistricts, setAvailableDistricts] = useState(null);
  const [selectedDistrict, setSelectedDistrict] = useState(null);
  const [availableCenters, setAvailableCenters] = useState(null);
  const [selectedCentre, setSelectedCentre] = useState(null);
  const [filterPwdFriendly, setFilterPwdFriendly] = useState(false);

  // Search state
  const [searchQuery, setSearchQuery] = useState("");

  // Course match state
  const [questions, setQuestions] = useState([]);
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [answers, setAnswers] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [recommendations, setRecommendations] = useState([]);
  const [showResults, setShowResults] = useState(false);

  // Previous recommendations state
  const [previousRecommendations, setPreviousRecommendations] = useState(null);
  const [checkingRecommendations, setCheckingRecommendations] = useState(true);

  // Enrollment state
  const [enrollingCourseId, setEnrollingCourseId] = useState(null);
  const [enrollingCentreId, setEnrollingCentreId] = useState(null);
  const [needsSupport, setNeedsSupport] = useState(null);
  const [enrollSubmitting, setEnrollSubmitting] = useState(false);
  const [enrollSuccess, setEnrollSuccess] = useState(false);
  const [enrolledCourseName, setEnrolledCourseName] = useState("");

  useEffect(() => {
    const verifyUser = async () => {
      try {
        setVerifying(true);
        setVerificationError(null);
        const data = await checkUserStatus(id, token);
        if (data?.success === false) {
          setVerificationError(data.message || "User not found. Please register first.");
          setCheckingRecommendations(false);
          return;
        }
        setUserStatus(data);

        // Check for previous recommended courses
        try {
          setCheckingRecommendations(true);
          const recData = await checkUserRecommendedCourses(id, token);
          if (recData?.success && recData?.matches?.length > 0) {
            setPreviousRecommendations(recData);
          } else {
            // No previous recommendations — start quiz flow
            fetchAllRegions();
          }
        } catch {
          // If check fails, just start the normal flow
          fetchAllRegions();
        } finally {
          setCheckingRecommendations(false);
        }
      } catch (err) {
        console.error("Error verifying user:", err);
        setVerificationError(
          err.response?.status === 404
            ? "User not found. Please register first."
            : "Unable to verify your account. Please try again."
        );
        setCheckingRecommendations(false);
      } finally {
        setVerifying(false);
      }
    };
    verifyUser();
  }, [id, token]); // eslint-disable-line react-hooks/exhaustive-deps

  const fetchAllRegions = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getAllRegions(token);
      setAllRegions(data);
    } catch (err) {
      setError("Failed to load regions. Please try again.");
      console.error("Error fetching regions:", err);
    } finally {
      setLoading(false);
    }
  };

  const fetchDistricts = useCallback(async (branchId) => {
    try {
      setLoading(true);
      setError(null);
      const data = await getDistrictsByBranch(branchId, token);
      setAvailableDistricts(data);
    } catch (err) {
      setError("Failed to load districts. Please try again.");
      console.error("Error fetching districts:", err);
    } finally {
      setLoading(false);
    }
  }, [token]);

  const fetchCenters = useCallback(async (districtId) => {
    try {
      setLoading(true);
      setError(null);
      const data = await getCentresByDistrict(districtId, token);
      setAvailableCenters(data);
    } catch (err) {
      setError("Failed to load centers. Please try again.");
      console.error("Error fetching centers:", err);
    } finally {
      setLoading(false);
    }
  }, [token]);

  const fetchQuestions = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getCourseMatchQuestions("Choice", token);
      setQuestions(data || []);
    } catch (err) {
      setError("Failed to load questions. Please try again.");
      console.error("Error fetching questions:", err);
    } finally {
      setLoading(false);
    }
  }, [token]);

  const handleRegionSelect = (region) => {
    setSelectedRegion(region);
    setSelectedDistrict(null);
    setSelectedCentre(null);
    setAvailableDistricts(null);
    setAvailableCenters(null);
    setSearchQuery("");
    resetQuiz();
    fetchDistricts(region.id);
    setStep(2);
  };

  const handleDistrictSelect = (district) => {
    setSelectedDistrict(district);
    setSelectedCentre(null);
    setAvailableCenters(null);
    setSearchQuery("");
    resetQuiz();
    fetchCenters(district.id);
    setStep(3);
  };

  const handleCentreSelect = (centre) => {
    const isSameCentre = selectedCentre?.id === centre.id;
    setSelectedCentre(centre);
    setSearchQuery("");
    if (!isSameCentre) {
      resetQuiz();
      fetchQuestions();
    }
    setStep(4);
  };

  // Course match handlers
  const getQuestionIcon = (tag) => {
    const iconMap = {
      experience: FiUser,
      timeCommitment: FiClock,
      careerGoal: FiTarget,
      interest: FiTrendingUp,
      priority: FiAward,
    };
    return iconMap[tag] || FiUser;
  };

  const handleAnswer = (questionId, optionId) => {
    const question = questions.find((q) => q.id === questionId);
    if (question?.is_multiple_select) {
      // Toggle option in array
      setAnswers((prev) => {
        const current = prev[questionId] || [];
        const updated = current.includes(optionId)
          ? current.filter((id) => id !== optionId)
          : [...current, optionId];
        return { ...prev, [questionId]: updated };
      });
    } else {
      setAnswers((prev) => ({ ...prev, [questionId]: optionId }));
      // Auto-advance after a brief delay so user sees their selection
      setTimeout(() => {
        if (currentQuestion < questions.length - 1) {
          setCurrentQuestion((prev) => prev + 1);
        } else {
          generateRecommendations();
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
      }, 300);
    }
  };

  const handleNextQuestion = () => {
    if (currentQuestion < questions.length - 1) {
      setCurrentQuestion((prev) => prev + 1);
    } else {
      generateRecommendations();
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const prevQuestion = () => {
    if (currentQuestion > 0) {
      setCurrentQuestion(currentQuestion - 1);
    }
  };

  const generateRecommendations = async () => {
    try {
      setSubmitting(true);
      setError(null);
      const optionIds = Object.values(answers).flat();
      const data = await getCourseRecommendations({
        optionIds,
        userId: id,
        regionId: selectedRegion?.id,
        centreId: selectedCentre?.id,
        token,
      });
      setRecommendations(data || []);
      setShowResults(true);
    } catch (err) {
      setError("Failed to get recommendations. Please try again.");
      console.error("Error getting recommendations:", err);
    } finally {
      setSubmitting(false);
    }
  };

  const resetQuiz = () => {
    setCurrentQuestion(0);
    setAnswers({});
    setRecommendations([]);
    setShowResults(false);
  };

  const handleStartQuizFlow = () => {
    setPreviousRecommendations(null);
    fetchAllRegions();
  };

  const handleEnrollClick = async (course) => {
    const courseId = course.course_id || course.id;
    const centreId = course.centre_id || selectedCentre?.id;
    setEnrolledCourseName(course.title);

    if (course.mode_of_delivery === "Online") {
      // Show support/accessibility modal
      setEnrollingCourseId(courseId);
      setEnrollingCentreId(centreId);
      setNeedsSupport(null);
    } else {
      // Enroll directly without modal
      try {
        setEnrollSubmitting(true);
        setError(null);
        await confirmCourse({
          userId: id,
          course_id: courseId,
          support: false,
          ...(centreId && { centre_id: centreId }),
        }, token);
        setEnrollSuccess(true);
      } catch (err) {
        const apiErrors = err.response?.data?.errors;
        const apiMessage = err.response?.data?.message;
        if (apiErrors) {
          setError(Object.values(apiErrors).flat().join(". "));
        } else {
          setError(apiMessage || "Failed to enroll. Please try again.");
        }
      } finally {
        setEnrollSubmitting(false);
      }
    }
  };

  const handleEnrollSubmit = async () => {
    try {
      setEnrollSubmitting(true);
      setError(null);
      const centreId = enrollingCentreId || selectedCentre?.id;
      await confirmCourse({
        userId: id,
        course_id: enrollingCourseId,
        support: needsSupport === true,
        ...(centreId && { centre_id: centreId }),
      }, token);
      setEnrollSuccess(true);
      setEnrollingCourseId(null);
      setEnrollingCentreId(null);
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      if (apiErrors) {
        setError(Object.values(apiErrors).flat().join(". "));
      } else {
        setError(apiMessage || "Failed to enroll. Please try again.");
      }
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const goToStep = (targetStep) => {
    // Allow going forward to step 4 if quiz progress exists
    const canGoForward = targetStep === 4 && selectedCentre && questions.length > 0;
    if (targetStep < step || canGoForward) {
      setStep(targetStep);
      setSearchQuery("");
      if (targetStep === 1) {
        setSelectedRegion(null);
        setSelectedDistrict(null);
        setSelectedCentre(null);
        setAvailableDistricts(null);
        setAvailableCenters(null);
        resetQuiz();
      } else if (targetStep === 2) {
        setSelectedDistrict(null);
        setSelectedCentre(null);
        setAvailableCenters(null);
        resetQuiz();
      } else if (targetStep === 3) {
        // Keep quiz progress — user can return to step 4 with answers intact
      }
    }
  };

  const stepLabels = ["Region", "District", "Center", "Course"];
  const activeQuestion = questions[currentQuestion];


  // Show verification state before allowing access
  if (verifying || checkingRecommendations) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center">
        <div className="text-center px-4">
          <div className="w-10 h-10 border-3 border-yellow-400 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600 text-sm">
            {verifying ? "Verifying your account..." : "Checking your courses..."}
          </p>
        </div>
      </div>
    );
  }

  if (verificationError) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
        <div className="flex items-center justify-center min-h-screen px-4">
          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4, ease: "easeOut" }}
            className="w-full max-w-md"
          >
            <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">

              <div className="px-6 py-8 sm:px-8 sm:py-10 text-center">
                {/* Icon */}
                <div className="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-5">
                  <FiAlertCircle className="w-7 h-7 text-red-500" />
                </div>

                {/* Title */}
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
                  We couldn&apos;t verify your account
                </h2>

                {/* Message */}
                <p className="text-gray-500 text-sm sm:text-base leading-relaxed mb-8 max-w-xs mx-auto">
                  {verificationError}
                </p>

                {/* Actions */}
                <div className="flex flex-col gap-3">
                  <button
                    onClick={() => window.location.reload()}
                    className="w-full py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium text-sm rounded-xl transition-colors"
                  >
                    Try Again
                  </button>
                </div>
              </div>
            </div>

          </motion.div>
        </div>
      </div>
    );
  }

  // Show previous recommendations if available
  if (previousRecommendations) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
        {/* Ghana flag gradient bar */}
        <div className="h-1.5 w-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600" />

        {/* Header */}
        <div className="bg-white border-b border-gray-200">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-3 py-3.5 sm:py-4">
              <Image
                src="/images/one-million-coders-logo.png"
                alt="One Million Coders"
                width={120}
                height={40}
                className="h-8 sm:h-10 w-auto flex-shrink-0"
              />
              <div className="min-w-0 flex-1">
                <h1 className="text-sm sm:text-xl font-bold text-gray-900">
                  Course Registration
                </h1>
                <p className="text-xs sm:text-sm text-gray-500 hidden sm:block">
                  One Million Coders Programme
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Enrollment Modal */}
        <AnimatePresence>
          {(enrollingCourseId || enrollSuccess) && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.2 }}
              className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
              onClick={(e) => {
                if (e.target === e.currentTarget && !enrollSubmitting && !enrollSuccess) {
                  setEnrollingCourseId(null);
                  setEnrollingCentreId(null);
                  setNeedsSupport(null);
                }
              }}
            >
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 10 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 10 }}
                transition={{ duration: 0.2 }}
                className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 sm:p-8 relative"
              >
                {enrollSuccess ? (
                  <div className="text-center">
                    <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                      <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                    </div>
                    <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                      You&apos;re enrolled!
                    </h2>
                    <p className="text-gray-500 text-sm sm:text-base mb-6">
                      You have been successfully enrolled in{" "}
                      <span className="font-semibold text-gray-700">{enrolledCourseName}</span>.
                    </p>
                    <button
                      onClick={() => router.push("/")}
                      className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                    >
                      Go to Home
                    </button>
                  </div>
                ) : (
                  <>
                    <button
                      onClick={() => {
                        setEnrollingCourseId(null);
                        setEnrollingCentreId(null);
                        setNeedsSupport(null);
                      }}
                      className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      <FiX className="w-5 h-5" />
                    </button>
                    <div className="text-center mb-5">
                      <h2 className="text-base sm:text-xl font-bold text-gray-900 mb-1">
                        One more thing
                      </h2>
                      <p className="text-gray-500 text-xs sm:text-sm">
                        Enrolling in <span className="font-medium text-gray-700">{enrolledCourseName}</span>
                      </p>
                    </div>

                    <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-4">
                      Do you require any special support or accessibility assistance?
                    </h3>

                    {error && (
                      <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{error}</p>
                      </div>
                    )}

                    <div className="grid grid-cols-2 gap-3 mb-6">
                      <button
                        onClick={() => setNeedsSupport(true)}
                        className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${
                          needsSupport === true
                            ? "bg-gray-900 text-white border-gray-900"
                            : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                        }`}
                      >
                        Yes, I do
                      </button>
                      <button
                        onClick={() => setNeedsSupport(false)}
                        className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${
                          needsSupport === false
                            ? "bg-gray-900 text-white border-gray-900"
                            : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                        }`}
                      >
                        No, thanks
                      </button>
                    </div>

                    <div className="flex items-center gap-3">
                      <button
                        onClick={() => {
                          setEnrollingCourseId(null);
                          setEnrollingCentreId(null);
                          setNeedsSupport(null);
                        }}
                        className="flex-1 py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium text-sm rounded-xl transition-colors"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={handleEnrollSubmit}
                        disabled={needsSupport === null || enrollSubmitting}
                        className={`flex-1 py-3 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2 ${
                          needsSupport !== null && !enrollSubmitting
                            ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900"
                            : "bg-gray-100 text-gray-400 cursor-not-allowed"
                        }`}
                      >
                        {enrollSubmitting ? (
                          <>
                            <FiLoader className="w-4 h-4 animate-spin" />
                            Enrolling...
                          </>
                        ) : (
                          "Confirm Enrollment"
                        )}
                      </button>
                    </div>
                  </>
                )}
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Previous Recommendations Content */}
        <motion.div
          className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8 lg:py-10"
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4, ease: "easeOut" }}
        >
          {error && !enrollingCourseId && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              className="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-xl flex items-start space-x-3"
            >
              <div className="flex-1 min-w-0">
                <p className="text-red-700 text-sm font-medium">{error}</p>
                <button
                  onClick={() => setError(null)}
                  className="text-red-600 text-xs sm:text-sm underline mt-1 hover:text-red-800 transition-colors"
                >
                  Dismiss
                </button>
              </div>
            </motion.div>
          )}

          {/* Results header */}
          <div className="text-center mb-6 sm:mb-10">
            <div className="w-12 h-12 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-5">
              <FiCheckCircle className="w-6 h-6 sm:w-8 sm:h-8 text-green-600" />
            </div>
            <h2 className="text-base sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">
              {previousRecommendations.title }
            </h2>
            <p className="text-gray-500 text-xs sm:text-base max-w-lg mx-auto">
              {previousRecommendations.description || "Based on your previous preferences, here are recommended courses that best align with your goals"}
            </p>
          </div>

          {/* Course cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
            {previousRecommendations.matches.map((course, index) => (
              <motion.div
                key={course.id}
                className="rounded-lg bg-white border border-gray-200 overflow-hidden"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.2, delay: Math.min(index * 0.04, 0.2) }}
              >
                <div className="relative h-28 sm:h-32 bg-gray-100">
                  {course.image && !imageErrors[course.id] ? (
                    <Image
                      src={course.image}
                      alt={course.title}
                      fill
                      className="object-cover"
                      onError={() => setImageErrors((prev) => ({ ...prev, [course.id]: true }))}
                    />
                  ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                      <Image
                        src="/images/one-million-coders-logo.png"
                        alt="One Million Coders"
                        width={80}
                        height={27}
                        className="opacity-15"
                      />
                    </div>
                  )}
                  <div className="absolute top-1.5 left-1.5 bg-gray-900 text-white rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center text-[9px] sm:text-[11px] font-bold">
                    {course.rank || `#${index + 1}`}
                  </div>
                  <div className="absolute top-1.5 right-1.5 flex items-center gap-1">
                    {course.match_percentage && (
                      <span
                        className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium backdrop-blur-sm ${
                          parseInt(course.match_percentage) >= 70
                            ? "bg-green-50/90 text-green-700"
                            : "bg-yellow-50/90 text-yellow-700"
                        }`}
                      >
                        <FiStar className="w-2.5 h-2.5" />
                        {course.match_percentage}
                      </span>
                    )}
                    {course.duration && (
                      <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-white/90 text-gray-600 rounded-full text-[10px] font-medium backdrop-blur-sm">
                        <FiClock className="w-2.5 h-2.5" />
                        {course.duration}
                      </span>
                    )}
                  </div>
                </div>
                <div className="p-2.5 sm:p-3">
                  <h3 className="text-xs sm:text-sm font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                    {course.title}
                  </h3>
                  {course.sub_title && (
                    <p className="text-[11px] sm:text-xs text-gray-500 mb-2 line-clamp-1">
                      {course.sub_title}
                    </p>
                  )}
                  {course.mode_of_delivery && (
                    <div className="flex items-center gap-1 mb-2">
                      <FiGlobe className="w-2.5 h-2.5 text-blue-600" />
                      <span className="text-[10px] sm:text-[11px] font-medium text-blue-700">{course.mode_of_delivery}</span>
                    </div>
                  )}
                  <button
                    onClick={() => handleEnrollClick(course)}
                    disabled={enrollSubmitting}
                    className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-xs rounded-lg transition-colors disabled:opacity-50"
                  >
                    Enroll Now
                    <FiChevronRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              </motion.div>
            ))}
          </div>

          {/* Retake Quiz button */}
          <div className="mt-8 sm:mt-10 flex justify-center">
            <button
              onClick={handleStartQuizFlow}
              className="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold text-sm rounded-xl transition-colors"
            >
              <FiTarget className="w-4 h-4" />
              Retake Quiz
            </button>
          </div>
        </motion.div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
      {/* Ghana flag gradient bar */}
      <div className="h-1.5 w-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600" />

      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Top row: logo + title + back */}
          <div className="flex items-center gap-3 py-3.5 sm:py-4">
            <Image
              src="/images/one-million-coders-logo.png"
              alt="One Million Coders"
              width={120}
              height={40}
              className="h-8 sm:h-10 w-auto flex-shrink-0"
            />
            <div className="min-w-0 flex-1">
              <h1 className="text-sm sm:text-xl font-bold text-gray-900">
                Course Registration
              </h1>
              <p className="text-xs sm:text-sm text-gray-500 hidden sm:block">
                One Million Coders Programme
              </p>
            </div>
            {step > 1 && (
              <button
                onClick={() => goToStep(step - 1)}
                className="flex items-center gap-1.5 text-xs sm:text-sm text-gray-500 hover:text-gray-700 transition-colors py-1.5 px-2.5 sm:px-3 rounded-lg hover:bg-gray-50 flex-shrink-0"
              >
                <FiArrowLeft className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                <span className="hidden sm:inline">Back</span>
              </button>
            )}
          </div>

          {/* Progress bar */}
          <div className="pb-4 sm:pb-4 pt-1.5 sm:pt-0 border-t border-gray-100 sm:border-t-0">
            <div className="flex items-center max-w-lg mx-auto">
              {[1, 2, 3, 4].map((num) => (
                <React.Fragment key={num}>
                  <button
                    onClick={() => goToStep(num)}
                    disabled={num >= step && !(num === 4 && selectedCentre && questions.length > 0)}
                    className="flex items-center gap-1 sm:gap-2 group flex-shrink-0"
                  >
                    <div
                      className={`w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold transition-all duration-300 ${
                        step > num || (num === 4 && step === 3 && selectedCentre && questions.length > 0)
                          ? "bg-green-500 text-white cursor-pointer group-hover:bg-green-600"
                          : step === num
                          ? "bg-yellow-400 text-gray-900 ring-2 sm:ring-4 ring-yellow-100"
                          : "bg-gray-200 text-gray-400"
                      }`}
                    >
                      {step > num || (num === 4 && step === 3 && selectedCentre && questions.length > 0) ? (
                        <FiCheckCircle className="w-3 h-3 sm:w-4 sm:h-4" />
                      ) : (
                        num
                      )}
                    </div>
                    <span
                      className={`text-[10px] sm:text-xs font-medium transition-colors ${
                        step >= num ? "text-gray-700" : "text-gray-400"
                      }`}
                    >
                      {stepLabels[num - 1]}
                    </span>
                  </button>
                  {num < 4 && (
                    <div
                      className={`flex-1 h-0.5 rounded-full mx-1.5 sm:mx-3 transition-all duration-500 ${
                        step > num ? "bg-green-400" : "bg-gray-200"
                      }`}
                    />
                  )}
                </React.Fragment>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Breadcrumb trail */}
      {(selectedRegion || selectedDistrict || selectedCentre) && (
        <div className="bg-white/80 backdrop-blur-sm border-b border-gray-100">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-2.5">
            <div className="flex items-center gap-1 sm:gap-2 text-[11px] sm:text-sm text-gray-500 min-w-0">
              {selectedRegion && (
                <button
                  onClick={() => goToStep(1)}
                  className="flex items-center gap-1 sm:gap-1.5 hover:text-yellow-600 transition-colors py-0.5 min-w-0"
                >
                  <FiMapPin className="w-3 h-3 sm:w-3.5 sm:h-3.5 flex-shrink-0" />
                  <span className="truncate max-w-[80px] sm:max-w-none">{selectedRegion.title}</span>
                </button>
              )}
              {selectedDistrict && (
                <>
                  <FiChevronRight className="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-300 flex-shrink-0" />
                  <button
                    onClick={() => goToStep(2)}
                    className="flex items-center gap-1 sm:gap-1.5 hover:text-yellow-600 transition-colors py-0.5 min-w-0"
                  >
                    <span className="truncate max-w-[80px] sm:max-w-none">{selectedDistrict.title}</span>
                  </button>
                </>
              )}
              {selectedCentre && (
                <>
                  <FiChevronRight className="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-300 flex-shrink-0" />
                  <button
                    onClick={() => goToStep(3)}
                    className="flex items-center gap-1 sm:gap-1.5 hover:text-yellow-600 transition-colors py-0.5 min-w-0"
                  >
                    <span className="truncate max-w-[80px] sm:max-w-none">{selectedCentre.title}</span>
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Main Content */}
      <motion.div
        className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8 lg:py-10"
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4, ease: "easeOut" }}
      >
        {error && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-xl flex items-start space-x-3"
          >
            <div className="flex-1 min-w-0">
              <p className="text-red-700 text-sm font-medium">{error}</p>
              <button
                onClick={() => setError(null)}
                className="text-red-600 text-xs sm:text-sm underline mt-1 hover:text-red-800 transition-colors"
              >
                Dismiss
              </button>
            </div>
          </motion.div>
        )}

        <AnimatePresence mode="wait" initial={false}>
          {/* Step 1: Region Selection */}
          {step === 1 && (
            <motion.div
              key="regions"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              <div className="mb-4 sm:mb-8">
                <h2 className="text-base sm:text-2xl font-bold text-gray-900">
                  Where would you like to train?
                </h2>
                <p className="text-gray-500 text-xs sm:text-base mt-0.5 sm:mt-1">
                  Select your region
                </p>
              </div>

              {loading ? (
                <div className="grid gap-2 sm:gap-3 grid-cols-1 sm:grid-cols-2">
                  {Array.from({ length: 8 }).map((_, i) => (
                    <div
                      key={i}
                      className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 animate-pulse"
                    >
                      <div className="h-4 sm:h-5 bg-gray-200 rounded w-3/4" />
                    </div>
                  ))}
                </div>
              ) : allRegions && allRegions.length > 0 ? (
                <>
                  <div className="relative mb-3 sm:mb-4">
                    <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search regions..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="w-full pl-9 pr-9 py-2.5 sm:py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 transition-all"
                    />
                    {searchQuery && (
                      <button
                        onClick={() => setSearchQuery("")}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <FiX className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  <div className="grid gap-2 sm:gap-3 grid-cols-1 sm:grid-cols-2">
                    {allRegions
                      .filter((region) =>
                        region.title.toLowerCase().includes(searchQuery.toLowerCase())
                      )
                      .map((region, index) => (
                    <motion.button
                      key={region.id}
                      onClick={() => handleRegionSelect(region)}
                      className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.97] group"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      transition={{ duration: 0.15, delay: Math.min(index * 0.02, 0.15) }}
                    >
                      <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                        {region.title}
                      </h3>
                    </motion.button>
                  ))}
                  {allRegions.filter((region) =>
                    region.title.toLowerCase().includes(searchQuery.toLowerCase())
                  ).length === 0 && (
                    <div className="col-span-1 sm:col-span-2 text-center py-8 bg-white rounded-xl border border-gray-200">
                      <p className="text-gray-500 text-xs sm:text-sm">
                        No regions match &ldquo;{searchQuery}&rdquo;
                      </p>
                    </div>
                  )}
                </div>
                </>
              ) : (
                !loading && (
                  <div className="text-center py-12 sm:py-20 bg-white rounded-2xl border border-gray-200">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                      <FiMapPin className="w-5 h-5 sm:w-7 sm:h-7 text-gray-400" />
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">
                      No regions available
                    </h3>
                    <p className="text-gray-500 mb-4 text-xs sm:text-sm">
                      Please try again later or contact support.
                    </p>
                    <Button
                      onClick={() => fetchAllRegions()}
                      variant="outline"
                      className="min-h-[44px]"
                    >
                      Try Again
                    </Button>
                  </div>
                )
              )}
            </motion.div>
          )}

          {/* Step 2: District Selection */}
          {step === 2 && selectedRegion && (
            <motion.div
              key="districts"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              <div className="mb-4 sm:mb-8">
                <h2 className="text-base sm:text-2xl font-bold text-gray-900">
                  Select your district
                </h2>
                <p className="text-gray-500 text-xs sm:text-base mt-0.5 sm:mt-1">
                  Districts in {selectedRegion.title}
                </p>
              </div>

              {loading ? (
                <div className="grid gap-2 sm:gap-3 grid-cols-1 sm:grid-cols-2">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <div
                      key={i}
                      className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 animate-pulse"
                    >
                      <div className="h-4 sm:h-5 bg-gray-200 rounded w-3/4" />
                    </div>
                  ))}
                </div>
              ) : availableDistricts?.districts &&
                availableDistricts.districts.length > 0 ? (
                <>
                  <div className="relative mb-3 sm:mb-4">
                    <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search districts..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="w-full pl-9 pr-9 py-2.5 sm:py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 transition-all"
                    />
                    {searchQuery && (
                      <button
                        onClick={() => setSearchQuery("")}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <FiX className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  <div className="grid gap-2 sm:gap-3 grid-cols-1 sm:grid-cols-2">
                    {availableDistricts.districts
                      .filter((district) =>
                        district.title.toLowerCase().includes(searchQuery.toLowerCase())
                      )
                      .map((district, index) => (
                      <motion.button
                        key={district.id}
                        onClick={() => handleDistrictSelect(district)}
                        className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.97] group"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        transition={{ duration: 0.15, delay: Math.min(index * 0.02, 0.15) }}
                      >
                        <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                          {district.title}
                        </h3>
                      </motion.button>
                    ))}
                    {availableDistricts.districts.filter((district) =>
                      district.title.toLowerCase().includes(searchQuery.toLowerCase())
                    ).length === 0 && (
                      <div className="col-span-1 sm:col-span-2 text-center py-8 bg-white rounded-xl border border-gray-200">
                        <p className="text-gray-500 text-xs sm:text-sm">
                          No districts match &ldquo;{searchQuery}&rdquo;
                        </p>
                      </div>
                    )}
                  </div>
                </>
              ) : (
                !loading && (
                  <div className="text-center py-12 sm:py-20 bg-white rounded-2xl border border-gray-200">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                      <FiMap className="w-5 h-5 sm:w-7 sm:h-7 text-gray-400" />
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">
                      No districts available
                    </h3>
                    <p className="text-gray-500 mb-4 text-xs sm:text-sm">
                      No districts found in {selectedRegion.title}.
                    </p>
                    <Button
                      onClick={() => goToStep(1)}
                      variant="outline"
                      className="min-h-[44px]"
                    >
                      Choose Different Region
                    </Button>
                  </div>
                )
              )}

            </motion.div>
          )}

          {/* Step 3: Centre Selection */}
          {step === 3 && selectedDistrict && (
            <motion.div
              key="centers"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              <div className="mb-4 sm:mb-6">
                <h2 className="text-base sm:text-2xl font-bold text-gray-900">
                  Choose a training center
                </h2>
                <p className="text-gray-500 text-xs sm:text-base mt-0.5 sm:mt-1">
                  Available centers in {selectedDistrict.title}
                </p>
              </div>

              {loading ? (
                <div className="space-y-2 sm:space-y-3">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <div
                      key={i}
                      className="w-full p-3 sm:p-5 rounded-xl bg-white border border-gray-200 animate-pulse"
                    >
                      <div className="h-4 sm:h-5 bg-gray-200 rounded w-2/3 mb-2" />
                      <div className="h-3 sm:h-4 bg-gray-100 rounded w-1/3" />
                    </div>
                  ))}
                </div>
              ) : availableCenters?.centres &&
                availableCenters.centres.length > 0 ? (
                <>
                  <div className="relative mb-3 sm:mb-4">
                    <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search centres..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="w-full pl-9 pr-9 py-2.5 sm:py-3 text-sm sm:text-base rounded-xl border border-gray-200 bg-white focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 transition-all"
                    />
                    {searchQuery && (
                      <button
                        onClick={() => setSearchQuery("")}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <FiX className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  {/* PWD filter toggle */}
                  {availableCenters.centres.some((c) => c.is_pwd_friendly) && (
                    <div className="mb-3 sm:mb-4">
                      <button
                        onClick={() => setFilterPwdFriendly(!filterPwdFriendly)}
                        className={`inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-[11px] sm:text-sm font-medium border transition-all duration-200 ${
                          filterPwdFriendly
                            ? "bg-purple-50 border-purple-300 text-purple-700"
                            : "bg-white border-gray-200 text-gray-600 hover:border-gray-300"
                        }`}
                      >
                        <span>♿</span>
                        Accessibility friendly
                        {filterPwdFriendly && (
                          <FiCheckCircle className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                        )}
                      </button>
                    </div>
                  )}

                  <div className="space-y-2 sm:space-y-3">
                    {availableCenters.centres
                      .filter((c) => !filterPwdFriendly || c.is_pwd_friendly)
                      .filter((c) =>
                        c.title.toLowerCase().includes(searchQuery.toLowerCase())
                      )
                      .map((centre, index) => {
                        const accessibilityFeatures = [
                          centre.wheelchair_accessible && "Wheelchair accessible",
                          centre.has_access_ramp && "Access ramp",
                          centre.has_accessible_toilet && "Accessible toilet",
                          centre.has_elevator && "Elevator",
                          centre.supports_hearing_impaired && "Hearing support",
                          centre.supports_visually_impaired && "Visual support",
                        ].filter(Boolean);

                        const hasExtras = centre.is_pwd_friendly || accessibilityFeatures.length > 0;

                        return (
                          <motion.button
                            key={centre.id}
                            onClick={() => handleCentreSelect(centre)}
                            className="w-full p-3 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.99] group"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ duration: 0.15, delay: Math.min(index * 0.02, 0.15) }}
                          >
                            <div className={`flex justify-between gap-2 ${hasExtras ? "items-start" : "items-center"}`}>
                              <div className={`flex gap-2.5 sm:gap-3 min-w-0 flex-1 ${hasExtras ? "items-start" : "items-center"}`}>
                                {hasExtras ? (
                                  <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                      <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                                        {centre.title}
                                      </h3>
                                      {centre.is_pwd_friendly && (
                                        <span className="flex-shrink-0 text-[10px] sm:text-xs bg-purple-50 text-purple-600 px-1.5 sm:px-2 py-0.5 rounded-full font-medium">
                                          ♿ PWD
                                        </span>
                                      )}
                                    </div>
                                    {accessibilityFeatures.length > 0 && (
                                      <div className="flex flex-wrap gap-1 sm:gap-1.5 mt-1.5">
                                        {accessibilityFeatures.map((feature) => (
                                          <span
                                            key={feature}
                                            className="text-[9px] sm:text-[11px] px-1.5 sm:px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full"
                                          >
                                            {feature}
                                          </span>
                                        ))}
                                      </div>
                                    )}
                                  </div>
                                ) : (
                                  <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                                    {centre.title}
                                  </h3>
                                )}
                              </div>
                              <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                            </div>
                          </motion.button>
                        );
                      })}
                    {filterPwdFriendly &&
                      availableCenters.centres.filter((c) => c.is_pwd_friendly)
                        .length === 0 && (
                        <div className="text-center py-8 sm:py-12 bg-white rounded-xl border border-gray-200">
                          <p className="text-gray-500 text-xs sm:text-sm mb-2">
                            No accessibility-friendly centers in this district.
                          </p>
                          <button
                            onClick={() => setFilterPwdFriendly(false)}
                            className="text-yellow-600 text-xs sm:text-sm font-medium hover:text-yellow-700"
                          >
                            Show all centers
                          </button>
                        </div>
                      )}
                    {searchQuery &&
                      availableCenters.centres
                        .filter((c) => !filterPwdFriendly || c.is_pwd_friendly)
                        .filter((c) =>
                          c.title.toLowerCase().includes(searchQuery.toLowerCase())
                        ).length === 0 && (
                        <div className="text-center py-8 bg-white rounded-xl border border-gray-200">
                          <p className="text-gray-500 text-xs sm:text-sm">
                            No centres match &ldquo;{searchQuery}&rdquo;
                          </p>
                        </div>
                      )}
                  </div>
                </>
              ) : (
                !loading && (
                  <div className="text-center py-12 sm:py-20 bg-white rounded-2xl border border-gray-200">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                      <FiMapPin className="w-5 h-5 sm:w-7 sm:h-7 text-gray-400" />
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">
                      No training centers available
                    </h3>
                    <p className="text-gray-500 mb-4 text-xs sm:text-sm">
                      No training centers found in {selectedDistrict.title}.
                    </p>
                    <Button
                      onClick={() => goToStep(2)}
                      variant="outline"
                      className="min-h-[44px]"
                    >
                      Choose Different District
                    </Button>
                  </div>
                )
              )}

            </motion.div>
          )}

          {/* Step 4: Course Match */}
          {step === 4 && selectedCentre && !showResults && (
            <motion.div
              key="course-match"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              <div className="mb-4 sm:mb-8">
                <h2 className="text-base sm:text-2xl font-bold text-gray-900">
                  Let&apos;s match you to a course
                </h2>
                <p className="text-gray-500 text-xs sm:text-base mt-0.5 sm:mt-1">
                  Answer a few questions so we can recommend the right courses for you
                </p>
              </div>

              {loading ? (
                <div className="animate-pulse">
                  <div className="mb-6 sm:mb-8">
                    <div className="flex items-center justify-between mb-2">
                      <div className="h-3 bg-gray-200 rounded w-24" />
                      <div className="h-3 bg-gray-200 rounded w-8" />
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5" />
                  </div>
                  <div className="text-center mb-6 sm:mb-10">
                    <div className="w-11 h-11 sm:w-14 sm:h-14 bg-gray-200 rounded-full mx-auto mb-4 sm:mb-6" />
                    <div className="h-5 sm:h-7 bg-gray-200 rounded w-3/4 mx-auto mb-2" />
                    <div className="h-4 sm:h-5 bg-gray-100 rounded w-1/2 mx-auto" />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-4 max-w-3xl mx-auto">
                    {Array.from({ length: 4 }).map((_, i) => (
                      <div
                        key={i}
                        className="p-4 sm:p-6 rounded-xl bg-white border border-gray-200"
                      >
                        <div className="h-4 sm:h-5 bg-gray-200 rounded w-5/6" />
                      </div>
                    ))}
                  </div>
                </div>
              ) : questions.length > 0 && activeQuestion ? (
                <>
                  {/* Quiz progress */}
                  <div className="mb-6 sm:mb-8">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[10px] sm:text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Question {currentQuestion + 1} of {questions.length}
                      </span>
                      <span className="text-[10px] sm:text-xs text-gray-400">
                        {Math.round(
                          ((currentQuestion + 1) / questions.length) * 100
                        )}
                        %
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5">
                      <div
                        className="bg-yellow-400 h-1.5 rounded-full transition-all duration-500 ease-out"
                        style={{
                          width: `${
                            ((currentQuestion + 1) / questions.length) * 100
                          }%`,
                        }}
                      />
                    </div>
                  </div>

                  {/* Question */}
                  <AnimatePresence mode="wait" initial={false}>
                    <motion.div
                      key={currentQuestion}
                      initial={{ opacity: 0, y: 6 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0 }}
                      transition={{ duration: 0.2, ease: "easeOut" }}
                    >
                      <div className="text-center mb-6 sm:mb-10">
                        <div className="w-11 h-11 sm:w-14 sm:h-14 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                          {React.createElement(
                            getQuestionIcon(activeQuestion.tag),
                            {
                              className:
                                "w-5 h-5 sm:w-6 sm:h-6 text-yellow-600",
                            }
                          )}
                        </div>
                        <h2 className="text-base sm:text-2xl font-bold text-gray-900 mb-1.5 sm:mb-3 leading-tight">
                          {activeQuestion.question}
                        </h2>
                        {activeQuestion.description && (
                          <p className="text-gray-500 text-xs sm:text-base max-w-2xl mx-auto">
                            {activeQuestion.description}
                          </p>
                        )}
                        {activeQuestion.is_multiple_select && (
                          <p className="text-yellow-600 text-[11px] sm:text-sm font-medium mt-2">
                            Select all that apply
                          </p>
                        )}
                      </div>

                      {/* Options */}
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-4 max-w-3xl mx-auto">
                        {activeQuestion.course_match_options?.map(
                          (option, index) => {
                            const isSelected = activeQuestion.is_multiple_select
                              ? (answers[activeQuestion.id] || []).includes(option.id)
                              : answers[activeQuestion.id] === option.id;
                            return (
                            <motion.button
                              key={option.id}
                              initial={{ opacity: 0 }}
                              animate={{ opacity: 1 }}
                              transition={{ duration: 0.15, delay: Math.min(index * 0.03, 0.12) }}
                              onClick={() =>
                                handleAnswer(activeQuestion.id, option.id)
                              }
                              className={`relative p-4 sm:p-6 rounded-xl text-left transition-all duration-200 border-2 ${
                                isSelected
                                  ? "bg-gray-900 text-white border-gray-900"
                                  : "bg-white border-gray-200 hover:border-yellow-400 active:scale-[0.98]"
                              }`}
                            >
                              <div className="flex items-start gap-3">
                                {/* Checkbox / Radio indicator */}
                                <div className="flex-shrink-0 mt-0.5">
                                  {activeQuestion.is_multiple_select ? (
                                    <div
                                      className={`w-5 h-5 sm:w-6 sm:h-6 rounded-md border-2 flex items-center justify-center transition-all duration-200 ${
                                        isSelected
                                          ? "bg-white border-white"
                                          : "border-gray-300"
                                      }`}
                                    >
                                      {isSelected && (
                                        <FiCheckCircle className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-900" />
                                      )}
                                    </div>
                                  ) : (
                                    <div
                                      className={`w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 flex items-center justify-center transition-all duration-200 ${
                                        isSelected
                                          ? "border-white"
                                          : "border-gray-300"
                                      }`}
                                    >
                                      {isSelected && (
                                        <div className="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-white" />
                                      )}
                                    </div>
                                  )}
                                </div>
                                <div className="flex-1 min-w-0">
                                  <h3 className="font-semibold text-sm sm:text-base mb-1">
                                    {option.answer}
                                  </h3>
                                  {option.description && (
                                    <p
                                      className={`text-xs sm:text-sm leading-relaxed ${
                                        isSelected
                                          ? "text-gray-300"
                                          : "text-gray-500"
                                      }`}
                                    >
                                      {option.description}
                                    </p>
                                  )}
                                </div>
                              </div>
                            </motion.button>
                            );
                          }
                        )}
                      </div>
                    </motion.div>
                  </AnimatePresence>

                  {/* Navigation */}
                  <div className="flex items-center justify-between mt-8 sm:mt-12 max-w-3xl mx-auto">
                    {currentQuestion > 0 ? (
                      <button
                        onClick={prevQuestion}
                        className="flex items-center gap-2 text-xs sm:text-sm text-gray-500 hover:text-gray-700 transition-colors py-2"
                      >
                        <FiArrowLeft className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        Previous
                      </button>
                    ) : (
                      <div />
                    )}
                    {submitting ? (
                      <div className="flex items-center gap-2 text-xs sm:text-sm text-gray-500">
                        <FiLoader className="w-3.5 h-3.5 sm:w-4 sm:h-4 animate-spin" />
                        Getting results...
                      </div>
                    ) : activeQuestion.is_multiple_select ? (
                      <button
                        onClick={handleNextQuestion}
                        disabled={(answers[activeQuestion.id] || []).length === 0}
                        className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 ${
                          (answers[activeQuestion.id] || []).length > 0
                            ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900"
                            : "bg-gray-100 text-gray-400 cursor-not-allowed"
                        }`}
                      >
                        {currentQuestion < questions.length - 1 ? "Next" : "Get Results"}
                        <FiChevronRight className="w-4 h-4" />
                      </button>
                    ) : null}
                  </div>
                </>
              ) : (
                !loading && (
                  <div className="text-center py-12 sm:py-20 bg-white rounded-2xl border border-gray-200">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                      <FiAlertCircle className="w-5 h-5 sm:w-7 sm:h-7 text-gray-400" />
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">
                      Questions unavailable
                    </h3>
                    <p className="text-gray-500 mb-4 text-xs sm:text-sm">
                      Could not load the course match questions.
                    </p>
                    <Button
                      onClick={() => fetchQuestions()}
                      variant="outline"
                      className="min-h-[44px]"
                    >
                      Try Again
                    </Button>
                  </div>
                )
              )}
            </motion.div>
          )}

          {/* Step 4: Results */}
          {step === 4 && selectedCentre && showResults && (
            <motion.div
              key="results"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              {/* Enrollment Modal */}
              <AnimatePresence>
                {(enrollingCourseId || enrollSuccess) && (
                  <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.2 }}
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                    onClick={(e) => {
                      if (e.target === e.currentTarget && !enrollSubmitting && !enrollSuccess) {
                        setEnrollingCourseId(null);
                        setNeedsSupport(null);
                      }
                    }}
                  >
                    <motion.div
                      initial={{ opacity: 0, scale: 0.95, y: 10 }}
                      animate={{ opacity: 1, scale: 1, y: 0 }}
                      exit={{ opacity: 0, scale: 0.95, y: 10 }}
                      transition={{ duration: 0.2 }}
                      className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 sm:p-8 relative"
                    >
                      {enrollSuccess ? (
                        <div className="text-center">
                          <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                          </div>
                          <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                            You&apos;re enrolled!
                          </h2>
                          <p className="text-gray-500 text-sm sm:text-base mb-6">
                            You have been successfully enrolled in{" "}
                            <span className="font-semibold text-gray-700">{enrolledCourseName}</span>.
                          </p>
                          <button
                            onClick={() => router.push("/")}
                            className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                          >
                            Go to Home
                          </button>
                        </div>
                      ) : (
                        <>
                          <button
                            onClick={() => {
                              setEnrollingCourseId(null);
                              setNeedsSupport(null);
                            }}
                            className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                          >
                            <FiX className="w-5 h-5" />
                          </button>
                          <div className="text-center mb-5">
                            <h2 className="text-base sm:text-xl font-bold text-gray-900 mb-1">
                              One more thing
                            </h2>
                            <p className="text-gray-500 text-xs sm:text-sm">
                              Enrolling in <span className="font-medium text-gray-700">{enrolledCourseName}</span>
                            </p>
                          </div>

                          <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-4">
                            Do you require any special support or accessibility assistance?
                          </h3>
                          <div className="grid grid-cols-2 gap-3 mb-6">
                            <button
                              onClick={() => setNeedsSupport(true)}
                              className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${
                                needsSupport === true
                                  ? "bg-gray-900 text-white border-gray-900"
                                  : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                              }`}
                            >
                              Yes, I do
                            </button>
                            <button
                              onClick={() => setNeedsSupport(false)}
                              className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${
                                needsSupport === false
                                  ? "bg-gray-900 text-white border-gray-900"
                                  : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                              }`}
                            >
                              No, thanks
                            </button>
                          </div>

                          <div className="flex items-center gap-3">
                            <button
                              onClick={() => {
                                setEnrollingCourseId(null);
                                setNeedsSupport(null);
                              }}
                              className="flex-1 py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium text-sm rounded-xl transition-colors"
                            >
                              Cancel
                            </button>
                            <button
                              onClick={handleEnrollSubmit}
                              disabled={needsSupport === null || enrollSubmitting}
                              className={`flex-1 py-3 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2 ${
                                needsSupport !== null && !enrollSubmitting
                                  ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900"
                                  : "bg-gray-100 text-gray-400 cursor-not-allowed"
                              }`}
                            >
                              {enrollSubmitting ? (
                                <>
                                  <FiLoader className="w-4 h-4 animate-spin" />
                                  Enrolling...
                                </>
                              ) : (
                                "Confirm Enrollment"
                              )}
                            </button>
                          </div>
                        </>
                      )}
                    </motion.div>
                  </motion.div>
                )}
              </AnimatePresence>

                  {/* Results header - course recommendations */}
                  <div className="text-center mb-6 sm:mb-10">
                    <div className="w-12 h-12 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-5">
                      <FiCheckCircle className="w-6 h-6 sm:w-8 sm:h-8 text-green-600" />
                    </div>
                    <h2 className="text-base sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">
                      Your Course Matches
                    </h2>
                    <p className="text-gray-500 text-xs sm:text-base max-w-lg mx-auto">
                      Based on your preferences, here are the courses that best fit
                      your goals
                    </p>
                  </div>

                  {recommendations.length > 0 ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                      {recommendations.map((course, index) => (
                        <motion.div
                          key={course.id}
                          className="rounded-lg bg-white border border-gray-200 overflow-hidden"
                          initial={{ opacity: 0 }}
                          animate={{ opacity: 1 }}
                          transition={{ duration: 0.2, delay: Math.min(index * 0.04, 0.2) }}
                        >
                          <div className="relative h-28 sm:h-32 bg-gray-100">
                            {course.image && !imageErrors[course.id] ? (
                              <Image
                                src={course.image}
                                alt={course.title}
                                fill
                                className="object-cover"
                                onError={() => setImageErrors((prev) => ({ ...prev, [course.id]: true }))}
                              />
                            ) : (
                              <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                <Image
                                  src="/images/one-million-coders-logo.png"
                                  alt="One Million Coders"
                                  width={80}
                                  height={27}
                                  className="opacity-15"
                                />
                              </div>
                            )}
                            <div className="absolute top-1.5 left-1.5 bg-gray-900 text-white rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center text-[9px] sm:text-[11px] font-bold">
                              #{index + 1}
                            </div>
                            <div className="absolute top-1.5 right-1.5 flex items-center gap-1">
                              {course.match_percentage != null && (
                                <span
                                  className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium backdrop-blur-sm ${
                                    course.match_percentage >= 70
                                      ? "bg-green-50/90 text-green-700"
                                      : "bg-yellow-50/90 text-yellow-700"
                                  }`}
                                >
                                  <FiStar className="w-2.5 h-2.5" />
                                  {course.match_percentage}%
                                </span>
                              )}
                              {course.duration && (
                                <span className="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-white/90 text-gray-600 rounded-full text-[10px] font-medium backdrop-blur-sm">
                                  <FiClock className="w-2.5 h-2.5" />
                                  {course.duration}
                                </span>
                              )}
                            </div>
                          </div>
                          <div className="p-2.5 sm:p-3">
                            <h3 className="text-xs sm:text-sm font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                              {course.title}
                            </h3>
                            {course.sub_title && (
                              <p className="text-[11px] sm:text-xs text-gray-500 mb-2 line-clamp-1">
                                {course.sub_title}
                              </p>
                            )}
                            {course.mode_of_delivery && (
                              <div className="flex items-center gap-1 mb-2">
                                <FiGlobe className="w-2.5 h-2.5 text-blue-600" />
                                <span className="text-[10px] sm:text-[11px] font-medium text-blue-700">{course.mode_of_delivery}</span>
                              </div>
                            )}
                            <button
                              onClick={() => handleEnrollClick(course)}
                              className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-xs rounded-lg transition-colors"
                            >
                              Enroll Now
                              <FiChevronRight className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </motion.div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-12 sm:py-20 bg-white rounded-2xl border border-gray-200">
                      <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                        <FiTarget className="w-5 h-5 sm:w-7 sm:h-7 text-gray-400" />
                      </div>
                      <h3 className="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">
                        No matches found
                      </h3>
                      <p className="text-gray-500 mb-4 text-xs sm:text-sm max-w-sm mx-auto">
                        We couldn&apos;t find courses matching your preferences. Try
                        retaking the quiz with different answers.
                      </p>
                      <Button
                        onClick={resetQuiz}
                        variant="outline"
                        className="min-h-[44px]"
                      >
                        Retake Quiz
                      </Button>
                    </div>
                  )}

                  {/* Actions */}
                  <div className="mt-8 sm:mt-10 flex justify-center">
                    <Button
                      onClick={() => router.push(`/programmes?user_id=${id}${selectedCentre ? `&centre_id=${selectedCentre.id}` : ''}`)}
                      className="min-h-[44px]"
                    >
                      View All Courses
                    </Button>
                  </div>
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  );
}
