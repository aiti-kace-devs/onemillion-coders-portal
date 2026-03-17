"use client";

import React, { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
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
  FiHome,
  FiStar,
  FiLoader,
  FiAlertCircle,
  FiGlobe,
} from "react-icons/fi";
import Button from "../../components/Button";
import {
  getCourseMatchQuestions,
  getCourseRecommendations,
} from "../../services/api";
import { getCourseImage } from "../../utils/courseImages";

export default function CourseMatchPage() {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(1);
  const [answers, setAnswers] = useState({});
  const [recommendations, setRecommendations] = useState([]);
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

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

  // Fetch questions on component mount
  useEffect(() => {
    const fetchQuestions = async () => {
      try {
        setLoading(true);
        setError(null);
        const questionsData = await getCourseMatchQuestions("General");
        setQuestions(questionsData || []);
      } catch (err) {
        console.error("Error fetching questions:", err);
        setError("Failed to load questions. Please try again later.");
      } finally {
        setLoading(false);
      }
    };

    fetchQuestions();
  }, []);

  const handleAnswer = (questionId, optionId) => {
    setAnswers((prev) => ({ ...prev, [questionId]: optionId }));
  };

  const nextStep = () => {
    if (currentStep < questions.length) {
      setCurrentStep(currentStep + 1);
    } else {
      generateRecommendations();
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  const generateRecommendations = async () => {
    try {
      setSubmitting(true);
      setError(null);

      // Get all selected option IDs
      const optionIds = Object.values(answers);

      // Call the recommendation API
      const recommendationsData = await getCourseRecommendations({ optionIds });
      setRecommendations(recommendationsData || []);
      setCurrentStep(questions.length + 1); // Results step
    } catch (err) {
      console.error("Error getting recommendations:", err);
      setError("Failed to get recommendations. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  const resetQuiz = () => {
    setCurrentStep(1);
    setAnswers({});
    setRecommendations([]);
    setError(null);
  };

  const currentQuestion = questions[currentStep - 1];
  const isAnswered = currentQuestion && answers[currentQuestion.id];
  const totalQuestions = questions.length;

  if (loading) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <FiLoader className="w-8 h-8 text-gray-400 animate-spin mx-auto mb-4" />
          <p className="text-gray-500">Loading questions...</p>
        </div>
      </div>
    );
  }

  if (error && questions.length === 0) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center max-w-md">
          <FiAlertCircle className="w-12 h-12 text-red-400 mx-auto mb-4" />
          <h2 className="text-xl font-medium text-gray-900 mb-2">
            Something went wrong
          </h2>
          <p className="text-gray-500 mb-6">{error}</p>
          <Button onClick={() => window.location.reload()}>Try Again</Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      {/* Minimal Progress Bar */}
      {currentStep <= totalQuestions && totalQuestions > 0 && (
        <div className="border-b border-gray-50">
          <div className="max-w-5xl mx-auto px-6 py-6">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-medium text-gray-400 uppercase tracking-wider">
                Step {currentStep} of {totalQuestions}
              </span>
              <span className="text-xs text-gray-400">
                {Math.round((currentStep / totalQuestions) * 100)}%
              </span>
            </div>
            <div className="w-full bg-gray-100 rounded-full h-1">
              <div
                className="bg-gray-900 h-1 rounded-full transition-all duration-700 ease-out"
                style={{ width: `${(currentStep / totalQuestions) * 100}%` }}
              />
            </div>
          </div>
        </div>
      )}

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-6 py-16">
        <AnimatePresence mode="wait">
          {currentStep <= totalQuestions && currentQuestion ? (
            <motion.div
              key={currentStep}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ duration: 0.4, ease: "easeOut" }}
            >
              {/* Question Header */}
              <div className="text-center mb-16">
                <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                  {React.createElement(getQuestionIcon(currentQuestion.tag), {
                    className: "w-7 h-7 text-gray-700",
                  })}
                </div>
                <h2 className="text-3xl font-light text-gray-900 mb-4 leading-tight">
                  {currentQuestion.question}
                </h2>
                <p className="text-gray-500 text-lg max-w-2xl mx-auto">
                  {currentQuestion.description}
                </p>
              </div>

              {/* Error message if any */}
              {error && (
                <div className="max-w-3xl mx-auto mb-8">
                  <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div className="flex items-center">
                      <FiAlertCircle className="w-5 h-5 text-red-400 mr-2" />
                      <p className="text-red-700">{error}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Options Grid */}
              <div className="grid md:grid-cols-2 gap-4 max-w-3xl mx-auto">
                {currentQuestion.course_match_options?.map((option, index) => (
                  <motion.button
                    key={option.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1, duration: 0.3 }}
                    onClick={() => handleAnswer(currentQuestion.id, option.id)}
                    className={`group relative p-8 rounded-2xl text-left transition-all duration-300 ${
                      answers[currentQuestion.id] === option.id
                        ? "bg-gray-900 text-white shadow-lg"
                        : "bg-gray-50 hover:bg-gray-100 text-gray-900"
                    }`}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h3 className="font-medium text-lg mb-2">
                          {option.answer}
                        </h3>
                        <p
                          className={`text-sm leading-relaxed ${
                            answers[currentQuestion.id] === option.id
                              ? "text-gray-300"
                              : "text-gray-500"
                          }`}
                        >
                          {option.description}
                        </p>
                      </div>
                      <div
                        className={`ml-4 transition-all duration-300 ${
                          answers[currentQuestion.id] === option.id
                            ? "opacity-100 scale-100"
                            : "opacity-0 scale-75"
                        }`}
                      >
                        <FiCheckCircle className="w-5 h-5" />
                      </div>
                    </div>
                  </motion.button>
                ))}
              </div>
            </motion.div>
          ) : (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
            >
              {/* Results Header */}
              <div className="text-center mb-16">
                <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8">
                  <FiCheckCircle className="w-10 h-10 text-green-600" />
                </div>
                <h2 className="text-3xl font-light text-gray-900 mb-4">
                  Your Course Matches
                </h2>
                <p className="text-gray-500 text-lg max-w-2xl mx-auto">
                  Based on your preferences, here are the courses that align
                  best with your goals
                </p>
              </div>

              {/* Recommendations */}
              <div className="space-y-8 max-w-6xl mx-auto">
                {recommendations.map((course, index) => (
                  <motion.div
                    key={course.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.15, duration: 0.4 }}
                    onClick={() => router.push(`/programmes/${course.id}`)}
                    className="group relative bg-white rounded-3xl shadow-sm hover:shadow-xl border border-gray-100 hover:border-gray-200 transition-all duration-500 cursor-pointer overflow-hidden"
                  >
                    {/* Match Badge */}
                    <div className="absolute top-6 right-6 z-10">
                      <div className="bg-white/95 backdrop-blur-sm rounded-full px-4 py-2 shadow-lg border border-green-100">
                        <div className="flex items-center gap-2">
                          <div
                            className={`w-3 h-3 rounded-full ${
                              course.match_percentage >= 90
                                ? "bg-green-500"
                                : course.match_percentage >= 70
                                ? "bg-yellow-500"
                                : course.match_percentage >= 50
                                ? "bg-orange-500"
                                : "bg-red-500"
                            }`}
                          ></div>
                          <span className="text-xs font-semibold text-gray-900">
                            {course.match_percentage}
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Ranking Badge */}
                    <div className="absolute top-6 left-6 z-10">
                      <div className="bg-gray-900 text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg">
                        #{index + 1}
                      </div>
                    </div>

                    <div className="flex flex-col lg:flex-row">
                      {/* Course Image */}
                      <div className="lg:w-80 h-64 lg:h-80 flex-shrink-0 relative overflow-hidden">
                        {/* TEMPORARY: Using static image for consistency instead of API image */}
                        <Image
                          // TEMPORARY: Commented out API image
                          // src={course.image}
                          src={getCourseImage(course.id)}
                          alt={course.title}
                          fill
                          className="object-cover group-hover:scale-105 transition-transform duration-700"
                          sizes="(max-width: 1024px) 100vw, 320px"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                        {/* TEMPORARY: Commented out conditional rendering for API images
                        {course.image ? (
                          <>
                            <Image
                              src={course.image}
                              alt={course.title}
                              fill
                              className="object-cover group-hover:scale-105 transition-transform duration-700"
                              sizes="(max-width: 1024px) 100vw, 320px"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                          </>
                        ) : (
                          <div className="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <FiTarget className="w-16 h-16 text-gray-400" />
                          </div>
                        )}
                        */}
                      </div>

                      {/* Course Content */}
                      <div className="flex-1 p-8 lg:p-10">
                        <div className="h-full flex flex-col">
                          {/* Header */}
                          <div className="mb-6">
                            <div className="flex flex-wrap items-center gap-3 mb-4">
                              <h3 className="text-xl lg:text-3xl font-bold text-gray-900 leading-tight">
                                {course.title}
                              </h3>
                              {/* <span
                                className={`px-4 py-1.5 text-xs font-semibold rounded-full ${
                                  course.level === "Beginner"
                                    ? "bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    : course.level === "Intermediate"
                                    ? "bg-amber-50 text-amber-700 border border-amber-200"
                                    : course.level === "Advanced"
                                    ? "bg-orange-50 text-orange-700 border border-orange-200"
                                    : "bg-blue-50 text-blue-700 border border-blue-200"
                                }`}
                              >
                                {course.level}
                              </span> */}
                            </div>

                            <p className="text-lg font-medium text-gray-700 mb-3">
                              {course.sub_title}
                            </p>

                            <p className="text-gray-600 leading-relaxed text-base">
                              {course.job_responsible}
                            </p>
                          </div>

                          {/* What you'll learn preview */}
                          {course.overview?.what_you_will_learn && (
                            <div className="mb-8">
                              <h4 className="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider">
                                What you&apos;ll learn
                              </h4>
                              <div className="grid sm:grid-cols-2 gap-3">
                                {course.overview.what_you_will_learn
                                  .slice(0, 4)
                                  .map((item, idx) => (
                                    <div
                                      key={idx}
                                      className="flex items-start gap-3"
                                    >
                                      <div className="w-2 h-2 rounded-full bg-green-500 mt-2 flex-shrink-0"></div>
                                      <span className="text-sm text-gray-600 leading-relaxed">
                                        {item}
                                      </span>
                                    </div>
                                  ))}
                              </div>
                              {course.overview.what_you_will_learn.length >
                                4 && (
                                <p className="text-sm text-gray-400 mt-3">
                                  +
                                  {course.overview.what_you_will_learn.length -
                                    4}{" "}
                                  more learning objectives
                                </p>
                              )}
                            </div>
                          )}

                          {/* Footer Info */}
                          <div className="mt-auto">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                              <div className="flex flex-wrap items-center gap-6">
                                <div className="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2">
                                  <FiClock className="w-4 h-4 text-gray-500" />
                                  <span className="text-sm font-medium text-gray-700">
                                    {course.duration}
                                  </span>
                                </div>
                                {course.mode_of_delivery && (
                                  <div className="flex items-center gap-2 bg-blue-50 rounded-full px-4 py-2">
                                    <FiGlobe className="w-4 h-4 text-blue-600" />
                                    <span className="text-sm font-medium text-blue-700">
                                      {course.mode_of_delivery}
                                    </span>
                                  </div>
                                )}
                                <div className="flex items-center gap-2 bg-blue-50 rounded-full px-4 py-2">
                                  <FiCheckCircle className="w-4 h-4 text-blue-600" />
                                  <span className="text-sm font-medium text-blue-700">
                                    {course.match_count} criteria matched
                                  </span>
                                </div>
                              </div>

                              <div className="flex items-center gap-3 text-gray-500 group-hover:text-gray-700 transition-colors">
                                <span className="text-sm font-medium">
                                  Explore Course
                                </span>
                                <div className="w-10 h-10 rounded-full bg-gray-100 group-hover:bg-gray-900 flex items-center justify-center transition-all duration-300">
                                  <FiArrowRight className="w-5 h-5 group-hover:text-white group-hover:translate-x-0.5 transition-all duration-300" />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Hover overlay */}
                    <div className="absolute inset-0 bg-gradient-to-r from-transparent to-blue-50/0 group-hover:to-blue-50/30 transition-all duration-500 pointer-events-none"></div>
                  </motion.div>
                ))}
              </div>

              {/* No Results Message */}
              {recommendations.length === 0 && !submitting && (
                <div className="text-center py-20">
                  <div className="max-w-md mx-auto">
                    <div className="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                      <FiTarget className="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 className="text-2xl font-bold text-gray-900 mb-3">
                      No perfect matches found
                    </h3>
                    <p className="text-gray-600 mb-8 leading-relaxed">
                      We couldn&apos;t find courses that perfectly match your
                      criteria. Try adjusting your preferences or retake the
                      quiz to explore more options.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                      <Button onClick={resetQuiz} variant="primary">
                        Retake Quiz
                      </Button>
                      <Link href="/programmes">
                        <Button variant="ghost">Browse All Courses</Button>
                      </Link>
                    </div>
                  </div>
                </div>
              )}

              {/* Action Buttons */}
              {recommendations.length > 0 && (
                <div className="text-center mt-20 pt-12 border-t border-gray-100">
                  <div className="max-w-2xl mx-auto mb-8">
                    <h3 className="text-xl font-semibold text-gray-900 mb-3">
                      Ready to start your journey?
                    </h3>
                    <p className="text-gray-600">
                      Found your perfect match? Register now or explore more
                      options to find the right fit for your goals.
                    </p>
                  </div>
                  <div className="flex flex-col sm:flex-row gap-4 justify-center max-w-md mx-auto">
                    <Link href="/register">
                    <Button
                      size="large"
                      className="flex-1 sm:flex-none"
                      >
                      Register Now
                    </Button>
                      </Link>
                    <div className="flex gap-3">
                      <Button onClick={resetQuiz} variant="ghost" size="large">
                        Retake Quiz
                      </Button>
                      <Link href="/programmes">
                        <Button variant="ghost" size="large">
                          Browse All
                        </Button>
                      </Link>
                    </div>
                  </div>
                </div>
              )}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Navigation */}
        {currentStep <= totalQuestions && totalQuestions > 0 && (
          <div className="flex items-center justify-between mt-16">
            <Button
              onClick={prevStep}
              variant="ghost"
              disabled={currentStep === 1}
              icon={FiArrowLeft}
              iconPosition="left"
              size="large"
            >
              Previous
            </Button>
            <Button
              onClick={nextStep}
              disabled={!isAnswered || submitting}
              icon={
                submitting
                  ? FiLoader
                  : currentStep === totalQuestions
                  ? FiCheckCircle
                  : FiArrowRight
              }
              size="large"
            >
              {submitting
                ? "Getting Results..."
                : currentStep === totalQuestions
                ? "Get Results"
                : "Next"}
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}
