"use client";

import React, { useState } from "react";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import {
  FiClock,
  FiArrowRight,
  FiCheckCircle,
  FiX,
  FiLoader,
  FiGlobe,
  FiInfo,
  FiMonitor,
  FiMapPin,
  FiCalendar,
  FiSun,
  FiArrowLeft,
  FiChevronRight,
  FiAlertCircle,
  FiTarget,
  FiStar,
} from "react-icons/fi";
import Button from "./Button";
import { confirmCourse } from "../services/pages";

const ProgrammeCard = ({ programme, userId, centreId, token }) => {
  const router = useRouter();
  const [showEnrollModal, setShowEnrollModal] = useState(false);
  const [needsSupport, setNeedsSupport] = useState(null);
  const [enrollSubmitting, setEnrollSubmitting] = useState(false);
  const [enrollSuccess, setEnrollSuccess] = useState(false);
  const [enrollError, setEnrollError] = useState(null);
  const [imageError, setImageError] = useState(false);
  const [imageErrors, setImageErrors] = useState({});

  // Enrollment sub-flow state (mockup)
  const [enrollmentStep, setEnrollmentStep] = useState(null);
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [selectedSession, setSelectedSession] = useState(null);
  const [waitlistJoined, setWaitlistJoined] = useState(false);
  const [enrolledCourseName, setEnrolledCourseName] = useState("");
  const [enrollingCentreId, setEnrollingCentreId] = useState(null);
  const [enrollingCentreTitle, setEnrollingCentreTitle] = useState(null);
  const [courseFullTab, setCourseFullTab] = useState("centres");

  // Mock data
  const MOCK_BATCHES = [
    {
      id: 1,
      name: "Batch A — Cohort 2026",
      startDate: "2026-05-12",
      endDate: "2026-08-12",
      duration: 90,
      totalSlots: 40,
      availableSlots: 12,
    },
    {
      id: 2,
      name: "Batch B — Cohort 2026",
      startDate: "2026-06-16",
      endDate: "2026-09-16",
      duration: 90,
      totalSlots: 40,
      availableSlots: 28,
    },
    {
      id: 3,
      name: "Batch C — Cohort 2026",
      startDate: "2026-09-01",
      endDate: "2026-12-01",
      duration: 90,
      totalSlots: 40,
      availableSlots: 40,
    },
    {
      id: 4,
      name: "Batch D — Cohort 2026",
      startDate: "2026-10-06",
      endDate: "2027-01-06",
      duration: 90,
      totalSlots: 40,
      availableSlots: 0,
    },
  ];
  const MOCK_SESSIONS = [
    {
      id: "morning",
      label: "Morning",
      time: "8:00 AM — 10:00 AM",
      description: "Best for early risers",
      hoursPerDay: 4,
    },
    {
      id: "afternoon",
      label: "Afternoon",
      time: "10:00 AM — 12:00 PM",
      description: "Perfect midday schedule",
      hoursPerDay: 4,
    },
    {
      id: "afternoon2",
      label: "Afternoon",
      time: "1:00 PM — 3:00 PM",
      description: "Perfect midday schedule",
      hoursPerDay: 4,
    },
    {
      id: "evening",
      label: "Evening",
      time: "3:00 PM — 5:00 PM",
      description: "Ideal for working professionals",
      hoursPerDay: 3,
    },
  ];
  const MOCK_ALT_CENTRES = [
    { id: 101, name: "Accra Digital Centre — East Legon", availableSlots: 8 },
    { id: 102, name: "Tema Innovation Hub", availableSlots: 15 },
  ];
  const MOCK_ALT_COURSES = [
    {
      id: 201,
      title: "Cybersecurity Professional",
      matchPercentage: "92%",
      availableSlots: 6,
      duration: "200 Hours",
      image: "/images/courses/cybersecurity-professional.JPG",
    },
    {
      id: 202,
      title: "Data Analyst Associate",
      matchPercentage: "85%",
      availableSlots: 14,
      duration: "80 Hours",
      image: "/images/courses/data-analyst-associate.JPG",
    },
  ];

  const handleEnrollClick = () => {
    setEnrolledCourseName(programme.title);
    setEnrollingCentreId(centreId);
    setEnrollingCentreTitle(null);
    setSelectedBatch(null);
    setSelectedSession(null);
    setNeedsSupport(null);
    setWaitlistJoined(false);
    setEnrollSuccess(false);
    setEnrollError(null);
    setEnrollmentStep("support");
    setShowEnrollModal(true);
  };

  const handleSupportAnswer = async (needs) => {
    setNeedsSupport(needs);
    if (!needs) {
      try {
        setEnrollSubmitting(true);
        setEnrollError(null);
        await confirmCourse(
          {
            userId,
            course_id: programme.course_id || programme.id,
            support: false,
            ...(centreId && { centre_id: centreId }),
          },
          token,
        );
        setEnrollSuccess(true);
      } catch (err) {
        const apiErrors = err.response?.data?.errors;
        const apiMessage = err.response?.data?.message;
        if (apiErrors) {
          setEnrollError(Object.values(apiErrors).flat().join(". "));
        } else {
          setEnrollError(apiMessage || "Failed to enroll. Please try again.");
        }
      } finally {
        setEnrollSubmitting(false);
      }
    } else {
      const hasAvailableBatch = MOCK_BATCHES.some((b) => b.availableSlots > 0);
      setEnrollmentStep(hasAvailableBatch ? "batch" : "courseFull");
    }
  };

  const handleBatchSelect = (batch) => {
    if (batch.availableSlots === 0) return;
    setSelectedBatch(batch);
    setEnrollmentStep("session");
  };

  const handleSessionSelect = (session) => {
    setSelectedSession(session);
    setEnrollmentStep("confirm");
  };

  const handleEnrollSubmit = async () => {
    if (selectedBatch?.id === 1) {
      setEnrollmentStep("batchFull");
      return;
    }
    try {
      setEnrollSubmitting(true);
      setEnrollError(null);
      const cId = enrollingCentreId || centreId;
      await confirmCourse(
        {
          userId,
          course_id: programme.course_id || programme.id,
          support: needsSupport === true,
          ...(cId && { centre_id: cId }),
        },
        token,
      );
      setEnrollSuccess(true);
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      if (apiErrors) {
        setEnrollError(Object.values(apiErrors).flat().join(". "));
      } else {
        setEnrollError(apiMessage || "Failed to enroll. Please try again.");
      }
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const closeEnrollmentModal = () => {
    setShowEnrollModal(false);
    setEnrollmentStep(null);
    setSelectedBatch(null);
    setSelectedSession(null);
    setNeedsSupport(null);
    setWaitlistJoined(false);
    setEnrollSuccess(false);
    setEnrollError(null);
  };

  const centreTitle = enrollingCentreTitle || "your centre";

  // Category color mapping
  const categoryColors = {
    Cybersecurity: "bg-red-50 text-red-700 border-red-100",
    "Data Protection": "bg-blue-50 text-blue-700 border-blue-100",
    "Artificial Intelligence": "bg-purple-50 text-purple-700 border-purple-100",
    "Software Development": "bg-emerald-50 text-emerald-700 border-emerald-100",
    "Cloud Computing": "bg-orange-50 text-orange-700 border-orange-100",
    "IT Support": "bg-indigo-50 text-indigo-700 border-indigo-100",
    "Data Analyst": "bg-teal-50 text-teal-700 border-teal-100",
    "Digital Marketing": "bg-pink-50 text-pink-700 border-pink-100",
    "Project Management": "bg-amber-50 text-amber-700 border-amber-100",
    "UI / UX Design": "bg-violet-50 text-violet-700 border-violet-100",
    "Digital Literacy": "bg-cyan-50 text-cyan-700 border-cyan-100",
  };

  const modeStyles = {
    Hybrid: {
      color: "bg-blue-50 text-blue-700 border-blue-100",
      icon: FiGlobe,
    },
    "In Person": {
      color: "bg-red-50 text-red-700 border-red-100",
      icon: FiMapPin,
    },
    Online: {
      color: "bg-purple-50 text-purple-700 border-purple-100",
      icon: FiMonitor,
    },
  };

  const currentMode = modeStyles[programme.mode_of_delivery] || {
    color: "bg-gray-50 text-gray-700 border-gray-100",
    icon: FiGlobe,
  };
  const ModeIcon = currentMode.icon;

  const getLevelColor = (level) => {
    const normalizedLevel = level?.trim().toLowerCase();
    const levelColors = {
      beginner: "bg-green-50 text-green-700",
      intermediate: "bg-yellow-50 text-yellow-700",
      advanced: "bg-blue-50 text-blue-700",
    };
    return levelColors[normalizedLevel] || "bg-green-50 text-green-700";
  };

  const getLevelBars = (level) => {
    const allBars = [
      { x: 8, y: 52, height: 20 },
      { x: 39, y: 32, height: 40 },
      { x: 70, y: 8, height: 64 },
    ];
    const filledCounts = { beginner: 1, intermediate: 2, advanced: 3 };
    const fillColors = {
      beginner: "#15803D",
      intermediate: "#A16207",
      advanced: "#1D4ED8",
    };
    const normalizedLevel = level?.trim().toLowerCase();
    const filledCount = filledCounts[normalizedLevel] || 1;
    const filledColor = fillColors[normalizedLevel] || "#000000";
    return (
      <svg
        viewBox="0 0 100 80"
        className="w-4 h-4"
        xmlns="http://www.w3.org/2000/svg"
      >
        {allBars.map((bar, index) => (
          <rect
            key={index}
            x={bar.x}
            y={bar.y}
            width="22"
            height={bar.height}
            rx="3"
            ry="3"
            fill={index < filledCount ? filledColor : "#D1D5DB"}
          />
        ))}
      </svg>
    );
  };

  // Step indicator component
  const StepIndicator = ({ current }) => {
    const steps = ["Batch", "Session", "Confirm"];
    return (
      <div className="flex items-center gap-1.5 mb-5">
        {steps.map((label, i) => {
          const isCurrent = i === current;
          const isDone = i < current;
          return (
            <React.Fragment key={label}>
              <div
                className={`flex items-center gap-1 ${isDone ? "text-green-500" : isCurrent ? "text-yellow-600" : "text-gray-300"}`}
              >
                <div
                  className={`w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ${isDone ? "bg-green-500 text-white" : isCurrent ? "bg-yellow-400 text-gray-900" : "bg-gray-100 text-gray-400"}`}
                >
                  {isDone ? <FiCheckCircle className="w-3 h-3" /> : i + 1}
                </div>
                <span className="text-[11px] font-medium">{label}</span>
              </div>
              {i < 2 && (
                <div
                  className={`flex-1 h-px ${isDone ? "bg-green-300" : "bg-gray-200"}`}
                />
              )}
            </React.Fragment>
          );
        })}
      </div>
    );
  };

  return (
    <div className="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-200 cursor-pointer group">
      {/* Image Container */}
      <div className="relative w-full h-48 bg-gray-100">
        {programme.image && !imageError ? (
          <Image
            src={programme.image}
            alt={programme.title}
            fill
            className="object-cover group-hover:scale-105 transition-transform duration-200"
            onError={() => setImageError(true)}
          />
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
            <Image
              src="/images/one-million-coders-logo.png"
              alt="One Million Coders"
              width={120}
              height={40}
              className="opacity-15"
            />
          </div>
        )}
        <div className="absolute top-4 left-4">
          <span
            onClick={(e) => {
              e.stopPropagation();
              if (window.location.pathname.startsWith("/programmes")) {
                router.push(`/programmes/category/${programme.category?.id}`);
              }
            }}
            className={`px-3 py-1 rounded-full text-xs font-medium border cursor-pointer hover:shadow-md transition-shadow ${categoryColors[programme.category?.title] || "bg-gray-100 text-gray-800 border-gray-200"}`}
          >
            {programme.category?.title}
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        <h3 className="text-xl font-semibold text-gray-900 mb-2 line-clamp-1">
          {programme.title}
        </h3>
        <div className="flex items-center justify-between gap-1 mb-2 transition-colors">
          <div className="text-sm text-gray-600 mb-4 line-clamp-1">
            {programme.sub_title}
          </div>
          {userId && (
            <a
              href={`/programmes/${programme.id}`}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1 mb-2 transition-colors"
            >
              <span className="text-[10px] sm:text-[11px] font-medium text-green-700">
                View Details
              </span>
              <FiInfo className="w-2.5 h-2.5 text-green-700" />
            </a>
          )}
        </div>

        <div className="flex items-center justify-between text-sm text-gray-600 mb-4">
          <div className="flex items-center space-x-2">
            <FiClock className="w-4 h-4" />
            <span>{programme.duration}</span>
          </div>
          <div className="flex items-center space-x-2">
            {programme.mode_of_delivery && (
              <span
                className={`flex items-center gap-1 px-2 py-1 rounded text-xs font-medium border ${currentMode.color}`}
              >
                <ModeIcon className="w-3 h-3" />
                {programme.mode_of_delivery}
              </span>
            )}
            <span
              className={`flex items-center gap-2 px-2 py-1 rounded text-xs font-medium ${getLevelColor(programme.level)}`}
            >
              {getLevelBars(programme.level)}
              {programme.level}
            </span>
          </div>
        </div>

        <p className="text-gray-600 text-sm mb-4 line-clamp-2">
          {programme.job_responsible}
        </p>

        <Button
          variant="primary"
          size="small"
          icon={FiArrowRight}
          iconPosition="right"
          className="w-full justify-center group-hover:shadow-lg transition-all duration-200"
          onClick={(e) => {
            e.stopPropagation();
            if (userId) {
              handleEnrollClick();
            } else {
              router.push(`/programmes/${programme.id}`);
            }
          }}
        >
          {userId ? "Enroll Now" : "Learn More"}
        </Button>
      </div>

      {/* Enrollment Sub-Flow Modal */}
      <AnimatePresence>
        {showEnrollModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 backdrop-blur-[2px] px-0 sm:px-4"
            onClick={(e) => {
              if (
                e.target === e.currentTarget &&
                !enrollSubmitting &&
                !enrollSuccess &&
                enrollmentStep !== "courseFull"
              ) {
                closeEnrollmentModal();
              }
            }}
          >
            <motion.div
              key={enrollmentStep || "success"}
              initial={{ opacity: 0, y: 40 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: 40 }}
              transition={{ type: "spring", damping: 28, stiffness: 300 }}
              className="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-lg px-5 py-6 sm:p-8 relative max-h-[85vh] sm:max-h-[90vh] overflow-y-auto"
            >
              {/* Success */}
              {enrollSuccess && (
                <div className="text-center">
                  <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                  </div>
                  <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                    You&apos;re enrolled!
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mb-2">
                    Successfully enrolled in{" "}
                    <span className="font-semibold text-gray-700">
                      {enrolledCourseName}
                    </span>
                    .
                  </p>
                  {selectedBatch && selectedSession && (
                    <p className="text-gray-400 text-xs sm:text-sm mb-6">
                      {selectedBatch.name} · {selectedSession.label} (
                      {selectedSession.time})
                    </p>
                  )}
                  <button
                    onClick={closeEnrollmentModal}
                    className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                  >
                    Close
                  </button>
                </div>
              )}

              {/* Waitlist Joined */}
              {!enrollSuccess && waitlistJoined && (
                <div className="text-center">
                  <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                  </div>
                  <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                    You&apos;re on the waitlist!
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mb-6">
                    We&apos;ll notify you when a slot opens for{" "}
                    <span className="font-semibold text-gray-700">
                      {enrolledCourseName}
                    </span>
                    .
                  </p>
                  <button
                    onClick={closeEnrollmentModal}
                    className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                  >
                    Close
                  </button>
                </div>
              )}

              {/* Support Question */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "support" && (
                  <>
                    <button
                      onClick={closeEnrollmentModal}
                      className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    >
                      <FiX className="w-4.5 h-4.5" />
                    </button>
                    <div className="flex justify-center mb-3 sm:hidden">
                      <div className="w-8 h-1 bg-gray-200 rounded-full" />
                    </div>
                    <div className="text-center mb-5">
                      <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        One quick question
                      </h2>
                      <p className="text-gray-400 text-[11px] sm:text-sm line-clamp-1">
                        Enrolling in{" "}
                        <span className="text-gray-600">
                          {enrolledCourseName}
                        </span>
                      </p>
                    </div>
                    <h3 className="text-[13px] sm:text-sm font-semibold text-gray-900 mb-3 text-center">
                      Do you need any accessibility support?
                    </h3>
                    {enrollError && (
                      <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{enrollError}</p>
                      </div>
                    )}
                    <div className="grid grid-cols-2 gap-2.5 mb-4">
                      <button
                        onClick={() => handleSupportAnswer(true)}
                        disabled={enrollSubmitting}
                        className="p-3.5 rounded-xl border-2 text-sm font-medium transition-all active:scale-[0.97] bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                      >
                        Yes, I do
                      </button>
                      <button
                        onClick={() => handleSupportAnswer(false)}
                        disabled={enrollSubmitting}
                        className="p-3.5 rounded-xl border-2 text-sm font-medium transition-all active:scale-[0.97] bg-white border-gray-200 hover:border-yellow-400 text-gray-700 flex items-center justify-center gap-2"
                      >
                        {enrollSubmitting ? (
                          <>
                            <FiLoader className="w-4 h-4 animate-spin" />
                            Enrolling...
                          </>
                        ) : (
                          "No, enroll me"
                        )}
                      </button>
                    </div>
                    <button
                      onClick={closeEnrollmentModal}
                      className="w-full py-2.5 text-sm text-gray-400 hover:text-gray-600 font-medium transition-colors"
                    >
                      Cancel
                    </button>
                  </>
                )}

              {/* Batch Selection */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "batch" && (
                  <>
                    <button
                      onClick={closeEnrollmentModal}
                      className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    >
                      <FiX className="w-4.5 h-4.5" />
                    </button>
                    <div className="flex justify-center mb-3 sm:hidden">
                      <div className="w-8 h-1 bg-gray-200 rounded-full" />
                    </div>
                    <StepIndicator current={0} />
                    <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-0.5">
                      When would you like to start?
                    </h2>
                    <p className="text-gray-400 text-[11px] sm:text-sm mb-4 sm:mb-5 line-clamp-1">
                      {enrolledCourseName}
                    </p>
                    <div className="space-y-2">
                      {MOCK_BATCHES.map((batch) => {
                        const isFull = batch.availableSlots === 0;
                        const fillPct =
                          ((batch.totalSlots - batch.availableSlots) /
                            batch.totalSlots) *
                          100;
                        const startStr = new Date(
                          batch.startDate,
                        ).toLocaleDateString("en-GB", {
                          day: "numeric",
                          month: "short",
                        });
                        const endStr = new Date(
                          batch.endDate,
                        ).toLocaleDateString("en-GB", {
                          day: "numeric",
                          month: "short",
                          year: "numeric",
                        });
                        return (
                          <button
                            key={batch.id}
                            onClick={() => handleBatchSelect(batch)}
                            disabled={isFull}
                            className={`w-full text-left p-3 sm:p-4 rounded-xl border transition-all duration-200 group ${isFull ? "bg-gray-50/80 border-gray-100 cursor-not-allowed" : "bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.99]"}`}
                          >
                            <div className="flex items-center gap-3">
                              <div
                                className={`w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0 ${isFull ? "bg-gray-100" : "bg-gradient-to-br from-yellow-50 to-orange-50 group-hover:from-yellow-100 group-hover:to-orange-100"} transition-colors`}
                              >
                                <span
                                  className={`text-[10px] font-medium leading-none ${isFull ? "text-gray-400" : "text-yellow-700"}`}
                                >
                                  {new Date(batch.startDate).toLocaleDateString(
                                    "en-GB",
                                    { month: "short" },
                                  )}
                                </span>
                                <span
                                  className={`text-base font-bold leading-tight ${isFull ? "text-gray-400" : "text-gray-900"}`}
                                >
                                  {new Date(batch.startDate).getDate()}
                                </span>
                              </div>
                              <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-0.5">
                                  <span
                                    className={`text-sm font-semibold ${isFull ? "text-gray-400" : "text-gray-900 group-hover:text-yellow-700"} transition-colors`}
                                  >
                                    {batch.name}
                                  </span>
                                  {isFull && (
                                    <span className="px-1.5 py-0.5 bg-red-50 text-red-500 text-[10px] font-medium rounded-full">
                                      Full
                                    </span>
                                  )}
                                </div>
                                <div
                                  className={`text-[11px] sm:text-xs ${isFull ? "text-gray-300" : "text-gray-500"} mb-1.5`}
                                >
                                  {startStr} — {endStr}
                                  <span className="hidden sm:inline">
                                    {" "}
                                    · {batch.duration} days
                                  </span>
                                </div>
                                <div className="flex items-center gap-2">
                                  <div className="flex-1 bg-gray-100 rounded-full h-1 overflow-hidden">
                                    <div
                                      className={`h-full rounded-full transition-all ${isFull ? "bg-red-300" : fillPct > 70 ? "bg-orange-400" : "bg-green-400"}`}
                                      style={{ width: `${fillPct}%` }}
                                    />
                                  </div>
                                  <span
                                    className={`text-[10px] tabular-nums ${isFull ? "text-gray-300" : batch.availableSlots <= 5 ? "text-orange-600 font-medium" : "text-gray-400"}`}
                                  >
                                    {batch.availableSlots} left
                                  </span>
                                </div>
                              </div>
                              {!isFull && (
                                <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                              )}
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  </>
                )}

              {/* Session Selection */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "session" && (
                  <>
                    <button
                      onClick={closeEnrollmentModal}
                      className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    >
                      <FiX className="w-4.5 h-4.5" />
                    </button>
                    <div className="flex justify-center mb-3 sm:hidden">
                      <div className="w-8 h-1 bg-gray-200 rounded-full" />
                    </div>
                    <StepIndicator current={1} />
                    <button
                      onClick={() => setEnrollmentStep("batch")}
                      className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                    >
                      <FiArrowLeft className="w-3 h-3" /> Back
                    </button>
                    <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                      Choose your session
                    </h2>
                    <div className="mb-4 px-2.5 py-1.5 bg-gradient-to-r from-yellow-50/80 to-transparent rounded-lg inline-flex items-center gap-1.5 text-[11px] sm:text-xs">
                      <FiCalendar className="w-3 h-3 text-yellow-600 flex-shrink-0" />
                      <span className="font-medium text-gray-600">
                        {selectedBatch?.name}
                      </span>
                    </div>
                    <div className="space-y-2">
                      {MOCK_SESSIONS.map((session) => {
                        const isSelected = selectedSession?.id === session.id;
                        return (
                          <button
                            key={session.id}
                            onClick={() => handleSessionSelect(session)}
                            className={`w-full text-left p-3 sm:p-4 rounded-xl border transition-all duration-200 group active:scale-[0.99] ${isSelected ? "bg-gray-900 text-white border-gray-900 shadow-lg" : "bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md"}`}
                          >
                            <div className="flex items-center gap-3">
                              <div
                                className={`w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors ${isSelected ? "bg-yellow-400" : "bg-gradient-to-br from-yellow-50 to-orange-50 group-hover:from-yellow-100 group-hover:to-orange-100"}`}
                              >
                                <FiClock
                                  className={`w-4 h-4 ${isSelected ? "text-gray-900" : "text-yellow-600"}`}
                                />
                              </div>
                              <div className="flex-1 min-w-0">
                                <div className="text-sm font-semibold">
                                  {session.time}
                                </div>
                                {/* <div className={`text-[11px] sm:text-xs ${isSelected ? "text-yellow-400" : "text-gray-900"}`}>{session.time}</div> */}
                              </div>
                              {!isSelected && (
                                <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                              )}
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  </>
                )}

              {/* Confirm */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "confirm" && (
                  <>
                    <button
                      onClick={closeEnrollmentModal}
                      className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    >
                      <FiX className="w-4.5 h-4.5" />
                    </button>
                    <div className="flex justify-center mb-3 sm:hidden">
                      <div className="w-8 h-1 bg-gray-200 rounded-full" />
                    </div>
                    <StepIndicator current={2} />
                    <button
                      onClick={() => setEnrollmentStep("session")}
                      className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                    >
                      <FiArrowLeft className="w-3 h-3" /> Back
                    </button>
                    <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                      Confirm enrollment
                    </h2>
                    <div className="mb-5 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100/50 border border-gray-200 overflow-hidden">
                      <div className="px-3 sm:px-4 py-2.5 sm:py-3 border-b border-gray-200/60">
                        <h3 className="text-[13px] sm:text-sm font-semibold text-gray-900 line-clamp-1">
                          {enrolledCourseName}
                        </h3>
                      </div>
                      <div className="px-3 sm:px-4 py-2 space-y-1.5">
                        <div className="flex items-center justify-between text-[11px] sm:text-xs">
                          <span className="text-gray-400">Batch</span>
                          <span className="font-medium text-gray-700">
                            {selectedBatch?.name}
                          </span>
                        </div>
                        <div className="flex items-center justify-between text-[11px] sm:text-xs">
                          <span className="text-gray-400">Session</span>
                          <span className="font-medium text-gray-700">
                            {selectedSession?.label} · {selectedSession?.time}
                          </span>
                        </div>
                      </div>
                    </div>
                    {enrollError && (
                      <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{enrollError}</p>
                      </div>
                    )}
                    <div className="flex items-center gap-2.5">
                      <button
                        onClick={closeEnrollmentModal}
                        className="flex-1 py-3 bg-gray-50 hover:bg-gray-100 text-gray-500 font-medium text-sm rounded-xl transition-colors"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={handleEnrollSubmit}
                        disabled={enrollSubmitting}
                        className={`flex-1 py-3 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2 active:scale-[0.97] ${!enrollSubmitting ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900 shadow-sm" : "bg-gray-100 text-gray-400 cursor-not-allowed"}`}
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

              {/* Centre Full — Tabbed */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "courseFull" && (
                  <>
                    <div className="flex justify-center mb-3 sm:hidden"><div className="w-8 h-1 bg-gray-200 rounded-full" /></div>
                    <button onClick={closeEnrollmentModal} className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"><FiX className="w-4.5 h-4.5" /></button>
                    <div className="text-center mb-4 sm:mb-5">
                      <div className="w-12 h-12 bg-gradient-to-br from-orange-50 to-red-50 rounded-2xl flex items-center justify-center mx-auto mb-3 ring-4 ring-orange-50"><FiAlertCircle className="w-6 h-6 text-orange-500" /></div>
                      <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-1">Centre is full</h2>
                      <p className="text-gray-500 text-[11px] sm:text-sm font-medium line-clamp-1 mb-0.5">{enrolledCourseName}</p>
                      <p className="text-gray-400 text-[11px] sm:text-xs line-clamp-1">No open batches at {centreTitle}</p>
                    </div>

                    {/* Tabs */}
                    <div className="flex bg-gray-100 rounded-xl p-1 mb-4">
                      <button onClick={() => setCourseFullTab("centres")} className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs sm:text-sm font-medium transition-all ${courseFullTab === "centres" ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700"}`}>
                        <FiMapPin className="w-3.5 h-3.5" />Same course
                      </button>
                      <button onClick={() => setCourseFullTab("courses")} className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs sm:text-sm font-medium transition-all ${courseFullTab === "courses" ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700"}`}>
                        <FiTarget className="w-3.5 h-3.5" />Same centre
                      </button>
                    </div>

                    <div className="mb-5">
                      {courseFullTab === "centres" && (
                        <>
                          <p className="text-[11px] sm:text-xs text-gray-400 mb-3">Other centres running <span className="text-gray-600">{enrolledCourseName}</span>:</p>
                          <div className="space-y-2">
                            {MOCK_ALT_CENTRES.map((alt) => (
                              <button key={alt.id} onClick={() => { setEnrollingCentreId(alt.id); setEnrollingCentreTitle(alt.name); setSelectedBatch(null); setSelectedSession(null); setCourseFullTab("centres"); setEnrollmentStep("batch"); }}
                                className="w-full text-left p-3 sm:p-3.5 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]">
                                <div className="flex items-center gap-3">
                                  <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center flex-shrink-0 group-hover:from-blue-100 group-hover:to-indigo-100 transition-colors"><FiMapPin className="w-4 h-4 text-blue-600" /></div>
                                  <div className="flex-1 min-w-0"><div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors truncate">{alt.name}</div></div>
                                  <div className="flex items-center gap-1.5 flex-shrink-0"><span className="text-xs font-bold text-green-600">{alt.availableSlots} slots</span><FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" /></div>
                                </div>
                              </button>
                            ))}
                          </div>
                        </>
                      )}
                      {courseFullTab === "courses" && (
                        <>
                          <p className="text-[11px] sm:text-xs text-gray-400 mb-3">Available courses at <span className="text-gray-600">{centreTitle}</span>:</p>
                          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            {MOCK_ALT_COURSES.map((alt) => (
                              <button key={alt.id} onClick={() => { setEnrolledCourseName(alt.title); setSelectedBatch(null); setSelectedSession(null); setCourseFullTab("centres"); setEnrollmentStep("batch"); }}
                                className="text-left rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] overflow-hidden">
                                <div className="relative h-20 sm:h-24 bg-gray-100">
                                  {alt.image && !imageErrors[`alt-${alt.id}`] ? (
                                    <Image src={alt.image} alt={alt.title} fill className="object-cover" onError={() => setImageErrors((prev) => ({ ...prev, [`alt-${alt.id}`]: true }))} />
                                  ) : (
                                    <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center"><Image src="/images/one-million-coders-logo.png" alt="One Million Coders" width={60} height={20} className="opacity-15" /></div>
                                  )}
                                  <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
                                  <div className="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-green-500/90 text-white text-[9px] sm:text-[10px] font-bold rounded-full backdrop-blur-sm">{alt.availableSlots} slots</div>
                                  {alt.matchPercentage && <div className="absolute top-1.5 left-1.5 flex items-center gap-0.5 px-1.5 py-0.5 bg-white/90 text-[9px] sm:text-[10px] font-medium rounded-full backdrop-blur-sm text-yellow-700"><FiStar className="w-2.5 h-2.5" />{alt.matchPercentage}</div>}
                                </div>
                                <div className="p-2 sm:p-2.5">
                                  <h4 className="text-[11px] sm:text-xs font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors line-clamp-2 leading-tight mb-1">{alt.title}</h4>
                                  <div className="flex items-center gap-1 text-[10px] text-gray-400"><FiClock className="w-2.5 h-2.5" /><span>{alt.duration}</span></div>
                                </div>
                              </button>
                            ))}
                          </div>
                        </>
                      )}
                    </div>

                    <div className="pt-4 border-t border-gray-100 space-y-2">
                      <button onClick={() => handleSupportAnswer(false)} disabled={enrollSubmitting} className="w-full p-3 sm:p-3.5 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 flex items-center justify-center flex-shrink-0 group-hover:from-green-100 group-hover:to-emerald-100 transition-colors"><FiCheckCircle className="w-4 h-4 text-green-600" /></div>
                          <div className="text-left flex-1"><div className="text-sm font-semibold text-gray-900">Enroll without support</div><div className="text-[11px] sm:text-xs text-gray-400">Skip support and enroll now</div></div>
                          {enrollSubmitting && <FiLoader className="w-4 h-4 text-gray-400 animate-spin flex-shrink-0" />}
                        </div>
                      </button>
                      <button onClick={() => setWaitlistJoined(true)} className="w-full p-3 sm:p-3.5 rounded-xl border border-dashed border-gray-300 hover:border-yellow-400 hover:bg-yellow-50/30 transition-all duration-200 group active:scale-[0.99]">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center flex-shrink-0 group-hover:bg-yellow-100 transition-colors"><FiClock className="w-4 h-4 text-yellow-600" /></div>
                          <div className="text-left flex-1"><div className="text-sm font-semibold text-gray-900">Join the waitlist</div><div className="text-[11px] sm:text-xs text-gray-400 line-clamp-1">Wait for a supported slot at {centreTitle}</div></div>
                        </div>
                      </button>
                    </div>
                  </>
                )}

              {/* Batch Full */}
              {!enrollSuccess &&
                !waitlistJoined &&
                enrollmentStep === "batchFull" && (
                  <>
                    <div className="flex justify-center mb-3 sm:hidden">
                      <div className="w-8 h-1 bg-gray-200 rounded-full" />
                    </div>
                    <button
                      onClick={closeEnrollmentModal}
                      className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                    >
                      <FiX className="w-4.5 h-4.5" />
                    </button>
                    <div className="text-center mb-5 sm:mb-6">
                      <div className="w-12 h-12 bg-gradient-to-br from-orange-50 to-red-50 rounded-2xl flex items-center justify-center mx-auto mb-3 ring-4 ring-orange-50">
                        <FiAlertCircle className="w-6 h-6 text-orange-500" />
                      </div>
                      <h2 className="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        Batch just filled up
                      </h2>
                      <p className="text-gray-400 text-[11px] sm:text-sm">
                        The last slot was just taken
                      </p>
                    </div>
                    <button
                      onClick={() => {
                        setEnrollmentStep("batch");
                        setSelectedBatch(null);
                        setSelectedSession(null);
                      }}
                      className="w-full mb-2 p-3 sm:p-3.5 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]"
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-50 to-orange-50 flex items-center justify-center flex-shrink-0 group-hover:from-yellow-100 group-hover:to-orange-100 transition-colors">
                          <FiCalendar className="w-4 h-4 text-yellow-600" />
                        </div>
                        <div className="flex-1 text-left">
                          <div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors">
                            Pick a different batch
                          </div>
                          <div className="text-xs text-gray-400">
                            Other batches may still have slots
                          </div>
                        </div>
                        <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" />
                      </div>
                    </button>
                    <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-4 mb-2.5">
                      Or try another centre
                    </h3>
                    <div className="space-y-2 mb-4">
                      {MOCK_ALT_CENTRES.map((alt) => (
                        <button
                          key={alt.id}
                          onClick={() => {
                            setEnrollingCentreId(alt.id);
                            setEnrollingCentreTitle(alt.name);
                            setSelectedBatch(null);
                            setSelectedSession(null);
                            setEnrollmentStep("batch");
                          }}
                          className="w-full text-left p-3 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]"
                        >
                          <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                              <FiMapPin className="w-4 h-4 text-blue-600" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <div className="text-sm font-medium text-gray-900 group-hover:text-yellow-700 transition-colors truncate">
                                {alt.name}
                              </div>
                            </div>
                            <div className="flex items-center gap-1.5 flex-shrink-0">
                              <span className="text-xs font-bold text-green-600">
                                {alt.availableSlots} slots
                              </span>
                              <FiChevronRight className="w-3.5 h-3.5 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" />
                            </div>
                          </div>
                        </button>
                      ))}
                    </div>
                    <div className="pt-3 border-t border-gray-100">
                      <button
                        onClick={() => setWaitlistJoined(true)}
                        className="w-full p-3 rounded-xl border border-dashed border-gray-300 hover:border-yellow-400 hover:bg-yellow-50/30 transition-all duration-200 group active:scale-[0.99]"
                      >
                        <div className="flex items-center gap-3">
                          <div className="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center flex-shrink-0 group-hover:bg-yellow-100 transition-colors">
                            <FiClock className="w-4 h-4 text-yellow-600" />
                          </div>
                          <div className="text-left">
                            <div className="text-sm font-medium text-gray-900">
                              Join the waitlist
                            </div>
                            <div className="text-xs text-gray-400">
                              Get notified when a slot opens
                            </div>
                          </div>
                        </div>
                      </button>
                    </div>
                  </>
                )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default ProgrammeCard;
