"use client";

import React, { useState, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import {
  FiArrowRight,
  FiArrowLeft,
  FiUser,
  FiClock,
  FiTarget,
  FiTrendingUp,
  FiAward,
  FiCheckCircle,
  FiStar,
  FiLoader,
  FiAlertCircle,
  FiGlobe,
  FiChevronRight,
  FiMapPin,
  FiSearch,
  FiX,
} from "react-icons/fi";
import Button from "../../components/Button";
import {
  getCourseMatchQuestions,
  getCourseRecommendations,
} from "../../services/api";
import { getAllRegions } from "../../services/pages";

export default function CourseMatchClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [step, setStep] = useState("region"); // "region" | "quiz" | "results"
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [answers, setAnswers] = useState({});
  const [recommendations, setRecommendations] = useState([]);
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [imageErrors, setImageErrors] = useState({});

  // Region state
  const [allRegions, setAllRegions] = useState(null);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");

  // Helper to update URL query params without navigation
  const updateQueryParams = useCallback((params) => {
    const url = new URL(window.location.href);
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        url.searchParams.set(key, value);
      } else {
        url.searchParams.delete(key);
      }
    });
    window.history.replaceState({}, "", url.toString());
  }, []);

  // Sync step and region to query params
  useEffect(() => {
    if (step === "region") return;
    updateQueryParams({
      step,
      region: selectedRegion?.id || null,
    });
  }, [step, selectedRegion, updateQueryParams]);

  // Icon mapping for different question tags
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

  // Restore progress from query params
  const restoreFromParams = async (regions) => {
    const savedStep = searchParams.get("step");
    const regionId = searchParams.get("region");

    if (!savedStep || savedStep === "region" || !regionId) return;

    try {
      const region = regions?.find((r) => String(r.id) === regionId);
      if (!region) return;
      setSelectedRegion(region);

      // If user was on quiz step, re-fetch questions
      if (savedStep === "quiz") {
        try {
          const questionsData = await getCourseMatchQuestions("General");
          setQuestions(questionsData || []);
        } catch {
          // If questions fail to load, user stays on region step
          return;
        }
      }

      setStep(savedStep);
    } catch {
      // If restore fails, user starts fresh
    }
  };

  // Fetch regions on component mount
  useEffect(() => {
    const fetchRegions = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await getAllRegions();
        setAllRegions(data);
        // After regions load, try to restore from query params
        await restoreFromParams(data);
      } catch (err) {
        console.error("Error fetching regions:", err);
        setError("Failed to load regions. Please try again later.");
      } finally {
        setLoading(false);
      }
    };

    fetchRegions();
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const fetchQuestions = async () => {
    try {
      setLoading(true);
      setError(null);
      const questionsData = await getCourseMatchQuestions("General");
      setQuestions(questionsData || []);
    } catch (err) {
      console.error("Error fetching questions:", err);
      setError("Failed to load questions. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const handleRegionSelect = (region) => {
    setSelectedRegion(region);
    setSearchQuery("");
    setStep("quiz");
    fetchQuestions();
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

      // Get all selected option IDs
      const optionIds = Object.values(answers).flat();

      // Call the recommendation API
      const recommendationsData = await getCourseRecommendations({
        optionIds,
        regionId: selectedRegion?.id,
      });
      setRecommendations(recommendationsData || []);
      setStep("results");
    } catch (err) {
      console.error("Error getting recommendations:", err);
      setError("Failed to get recommendations. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  const resetQuiz = () => {
    setCurrentQuestion(0);
    setAnswers({});
    setRecommendations([]);
    setStep("region");
    setSelectedRegion(null);
    setSearchQuery("");
    setQuestions([]);
    setError(null);
    updateQueryParams({ step: null, region: null });
  };

  const activeQuestion = questions[currentQuestion];
  const totalQuestions = questions.length;

  if (loading && step === "region" && !allRegions) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center">
        <div className="text-center px-4">
          <div className="w-10 h-10 border-3 border-yellow-400 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600 text-sm">Loading...</p>
        </div>
      </div>
    );
  }

  if (error && step === "region" && !allRegions) {
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
                <div className="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-5">
                  <FiAlertCircle className="w-7 h-7 text-red-500" />
                </div>
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
                  Something went wrong
                </h2>
                <p className="text-gray-500 text-sm sm:text-base leading-relaxed mb-8 max-w-xs mx-auto">
                  {error}
                </p>
                <button
                  onClick={() => window.location.reload()}
                  className="w-full py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium text-sm rounded-xl transition-colors"
                >
                  Try Again
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
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
          {step === "region" && (
            <motion.div
              key="regions"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              <div className="mb-4 sm:mb-8">
                <h2 className="text-base sm:text-2xl font-bold text-gray-900">
                  Where are you located?
                </h2>
                <p className="text-gray-500 text-xs sm:text-base mt-0.5 sm:mt-1">
                  Select your region to get started
                </p>
              </div>

              {allRegions && allRegions.length > 0 ? (
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
                        region.title
                          .toLowerCase()
                          .includes(searchQuery.toLowerCase()),
                      )
                      .map((region, index) => (
                        <motion.button
                          key={region.id}
                          onClick={() => handleRegionSelect(region)}
                          className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.97] group"
                          initial={{ opacity: 0 }}
                          animate={{ opacity: 1 }}
                          transition={{
                            duration: 0.15,
                            delay: Math.min(index * 0.02, 0.15),
                          }}
                        >
                          <div className="flex items-center gap-2.5 sm:gap-3">
                            <FiMapPin className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400 group-hover:text-yellow-600 flex-shrink-0" />
                            <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                              {region.title}
                            </h3>
                          </div>
                        </motion.button>
                      ))}
                    {allRegions.filter((region) =>
                      region.title
                        .toLowerCase()
                        .includes(searchQuery.toLowerCase()),
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
                      Please try again later.
                    </p>
                    <Button
                      onClick={() => window.location.reload()}
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

          {/* Step 2: Quiz Questions */}
          {step === "quiz" && (
            <motion.div
              key="course-match"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              {totalQuestions > 0 && activeQuestion ? (
                <>
                  {/* Quiz progress */}
                  <div className="mb-6 sm:mb-8">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[10px] sm:text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Question {currentQuestion + 1} of {totalQuestions}
                      </span>
                      <span className="text-[10px] sm:text-xs text-gray-400">
                        {Math.round(
                          ((currentQuestion + 1) / totalQuestions) * 100,
                        )}
                        %
                      </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-1.5">
                      <div
                        className="bg-yellow-400 h-1.5 rounded-full transition-all duration-500 ease-out"
                        style={{
                          width: `${((currentQuestion + 1) / totalQuestions) * 100
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
                            },
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
                              ? (answers[activeQuestion.id] || []).includes(
                                option.id,
                              )
                              : answers[activeQuestion.id] === option.id;
                            return (
                              <motion.button
                                key={option.id}
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{
                                  duration: 0.15,
                                  delay: Math.min(index * 0.03, 0.12),
                                }}
                                onClick={() =>
                                  handleAnswer(activeQuestion.id, option.id)
                                }
                                className={`relative p-4 sm:p-6 rounded-xl text-left transition-all duration-200 border-2 ${isSelected
                                    ? "bg-gray-900 text-white border-gray-900"
                                    : "bg-white border-gray-200 hover:border-yellow-400 active:scale-[0.98]"
                                  }`}
                              >
                                <div className="flex items-start gap-3">
                                  {/* Checkbox / Radio indicator */}
                                  <div className="flex-shrink-0 mt-0.5">
                                    {activeQuestion.is_multiple_select ? (
                                      <div
                                        className={`w-5 h-5 sm:w-6 sm:h-6 rounded-md border-2 flex items-center justify-center transition-all duration-200 ${isSelected
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
                                        className={`w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 flex items-center justify-center transition-all duration-200 ${isSelected
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
                                        className={`text-xs sm:text-sm leading-relaxed ${isSelected
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
                          },
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
                        disabled={
                          (answers[activeQuestion.id] || []).length === 0
                        }
                        className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 ${(answers[activeQuestion.id] || []).length > 0
                            ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900"
                            : "bg-gray-100 text-gray-400 cursor-not-allowed"
                          }`}
                      >
                        {currentQuestion < questions.length - 1
                          ? "Next"
                          : "Get Results"}
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
                      Could not load the course recommendation questions.
                    </p>
                    <Button
                      onClick={() => window.location.reload()}
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

          {/* Step 3: Results */}
          {step === "results" && (
            <motion.div
              key="results"
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.25, ease: "easeOut" }}
            >
              {/* Results header */}
              <div className="text-center mb-6 sm:mb-10">
                <div className="w-12 h-12 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-5">
                  <FiCheckCircle className="w-6 h-6 sm:w-8 sm:h-8 text-green-600" />
                </div>
                <h2 className="text-base sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">
                  Your Course Recommendations
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
                      className="rounded-lg bg-white border border-gray-200 overflow-hidden cursor-pointer hover:shadow-md transition-shadow"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      transition={{
                        duration: 0.2,
                        delay: Math.min(index * 0.04, 0.2),
                      }}
                      onClick={() => router.push(`/programmes/${course.id}`)}
                    >
                      <div className="relative h-28 sm:h-32 bg-gray-100">
                        {course.image && !imageErrors[course.id] ? (
                          <Image
                            src={course.image}
                            alt={course.title}
                            fill
                            className="object-cover"
                            onError={() =>
                              setImageErrors((prev) => ({
                                ...prev,
                                [course.id]: true,
                              }))
                            }
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
                              className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium backdrop-blur-sm ${course.match_percentage.split("%")[0] >= 70
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
                            <span className="text-[10px] sm:text-[11px] font-medium text-blue-700">
                              {course.mode_of_delivery}
                            </span>
                          </div>
                        )}
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            router.push(`/programmes/${course.id}`);
                          }}
                          className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-xs rounded-lg transition-colors"
                        >
                          Explore Course
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
                  <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <Button
                      onClick={resetQuiz}
                      variant="outline"
                      className="min-h-[44px]"
                    >
                      Get New Recommendations
                    </Button>
                    <Link href="/programmes">
                      <Button variant="ghost" className="min-h-[44px]">
                        Browse All Courses
                      </Button>
                    </Link>
                  </div>
                </div>
              )}

              {/* Actions */}
              {recommendations.length > 0 && (
                <div className="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-3 justify-center items-center">
                  <button
                    onClick={resetQuiz}
                    className="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold text-sm rounded-xl transition-colors"
                  >
                    <FiTarget className="w-4 h-4" />
                    Get New Recommendations
                  </button>
                  <Link href="/programmes">
                    <button className="inline-flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm rounded-xl border border-gray-200 transition-colors">
                      Browse All Courses
                      <FiArrowRight className="w-4 h-4" />
                    </button>
                  </Link>
                </div>
              )}
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  );
}
