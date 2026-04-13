"use client";

import { useState } from "react";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import { FiClock, FiArrowRight, FiCheckCircle, FiX, FiLoader, FiGlobe, FiInfo, FiMonitor, FiMapPin } from "react-icons/fi";
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

  const handleEnrollSubmit = async () => {
    try {
      setEnrollSubmitting(true);
      setEnrollError(null);
      await confirmCourse({
        userId,
        course_id: programme.course_id || programme.id,
        support: needsSupport === true,
        ...(centreId && { centre_id: centreId }),
      }, token);
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

  // Category color mapping
  const categoryColors = {
    "Cybersecurity": "bg-red-50 text-red-700 border-red-100",
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

  // Mode of delivery mapping
  const modeStyles = {
    "Hybrid": {
      color: "bg-blue-50 text-blue-700 border-blue-100",
      icon: FiGlobe
    },
    "In Person": {
      color: "bg-red-50 text-red-700 border-red-100",
      icon: FiMapPin
    },
    "Online": {
      color: "bg-purple-50 text-purple-700 border-purple-100",
      icon: FiMonitor
    }
  };

  const currentMode = modeStyles[programme.mode_of_delivery] || {
    color: "bg-gray-50 text-gray-700 border-gray-100",
    icon: FiGlobe
  };

  const ModeIcon = currentMode.icon;
  // Level color mapping for programme level badges
  const getLevelColor = (level) => {
    const normalizedLevel = level?.trim().toLowerCase();
    const levelColors = {
      'beginner': 'bg-green-50 text-green-700',
      'intermediate': 'bg-yellow-50 text-yellow-700',
      'advanced': 'bg-blue-50 text-blue-700',
    };
    return levelColors[normalizedLevel] || 'bg-green-50 text-green-700';
  };

  // Custom SVG bars for programme level badges
  const getLevelBars = (level) => {
    const allBars = [
      { x: 8, y: 52, height: 20 },   // bar 1 (short)
      { x: 39, y: 32, height: 40 },  // bar 2 (medium)
      { x: 70, y: 8, height: 64 }    // bar 3 (tall)
    ];

    const filledCounts = {
      'beginner': 1,
      'intermediate': 2,
      'advanced': 3
    };

    const fillColors = {
      beginner: '#15803D', // green-700
      intermediate: '#A16207', // yellow-700
      advanced: '#1D4ED8', // blue-700
    };

    const normalizedLevel = level?.trim().toLowerCase();
    const filledCount = filledCounts[normalizedLevel] || 1;
    const filledColor = fillColors[normalizedLevel] || '#000000';

    return (
      <svg viewBox="0 0 100 80" className="w-4 h-4" xmlns="http://www.w3.org/2000/svg">
        {allBars.map((bar, index) => (
          <rect
            key={index}
            x={bar.x}
            y={bar.y}
            width="22"
            height={bar.height}
            rx="3"
            ry="3"
            fill={index < filledCount ? filledColor : '#D1D5DB'}
          />
        ))}
      </svg>
    );
  };

  return (
    <div
      className="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-200 cursor-pointer group"
    >
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
        {/* Category Tag Overlay */}
        <div className="absolute top-4 left-4">
          <span
            onClick={(e) => {
              e.stopPropagation();
              // Only navigate to category page if we're already on the programmes page
              if (window.location.pathname.startsWith('/programmes')) {
                router.push(`/programmes/category/${programme.category?.id}`);
              }
            }}
            className={`px-3 py-1 rounded-full text-xs font-medium border cursor-pointer hover:shadow-md transition-shadow ${categoryColors[programme.category?.title] || "bg-gray-100 text-gray-800 border-gray-200"
              }`}
          >
            {programme.category?.title}
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        {/* Title */}
        <h3 className="text-xl font-semibold text-gray-900 mb-2 line-clamp-1">{programme.title}</h3>
        <div className="flex items-center justify-between gap-1 mb-2 transition-colors">
          <div className="text-sm text-gray-600 mb-4 line-clamp-1">{programme.sub_title}</div>
          {userId && (
            <a href={`/programmes/${programme.id}`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 mb-2 transition-colors">
              <span className="text-[10px] sm:text-[11px] font-medium text-green-700">View Details</span>
              <FiInfo className="w-2.5 h-2.5 text-green-700" />
            </a>
          )}
        </div>


        {/* Stats */}
        <div className="flex items-center justify-between text-sm text-gray-600 mb-4">
          <div className="flex items-center space-x-2">
            <FiClock className="w-4 h-4" />
            <span>{programme.duration}</span>
          </div>
          <div className="flex items-center space-x-2">
            {programme.mode_of_delivery && (
              <span className={`flex items-center gap-1 px-2 py-1 rounded text-xs font-medium border ${currentMode.color}`}>
                <ModeIcon className="w-3 h-3" />
                {programme.mode_of_delivery}
              </span>
            )}
            {/* Level Badge highlight color and bars */}
            <span className={`flex items-center gap-2 px-2 py-1 rounded text-xs font-medium ${getLevelColor(programme.level)}`}>
              {getLevelBars(programme.level)}
              {programme.level}
            </span>
          </div>
        </div>

        {/* Description */}
        <p className="text-gray-600 text-sm mb-4 line-clamp-2">
          {programme.job_responsible}
        </p>

        {/* Action Button */}
        <Button
          variant="primary"
          size="small"
          icon={FiArrowRight}
          iconPosition="right"
          className="w-full justify-center group-hover:shadow-lg transition-all duration-200"
          onClick={async (e) => {
            e.stopPropagation();
            if (userId) {
              if (programme.mode_of_delivery === "Online") {
                // Show support/accessibility modal
                setShowEnrollModal(true);
                setNeedsSupport(null);
                setEnrollSuccess(false);
                setEnrollError(null);
              } else {
                // Enroll directly without modal
                try {
                  setEnrollSubmitting(true);
                  setEnrollError(null);
                  await confirmCourse({
                    userId,
                    course_id: programme.course_id || programme.id,
                    support: false,
                    ...(centreId && { centre_id: centreId }),
                  }, token);
                  setEnrollSuccess(true);
                  setShowEnrollModal(true);
                } catch (err) {
                  const apiErrors = err.response?.data?.errors;
                  const apiMessage = err.response?.data?.message;
                  if (apiErrors) {
                    setEnrollError(Object.values(apiErrors).flat().join(". "));
                  } else {
                    setEnrollError(apiMessage || "Failed to enroll. Please try again.");
                  }
                  setShowEnrollModal(true);
                } finally {
                  setEnrollSubmitting(false);
                }
              }
            } else {
              router.push(`/programmes/${programme.id}`);
            }
          }}
        >
          {userId ? "Enroll Now" : "Learn More"}
        </Button>
      </div>

      {/* Enrollment Modal */}
      <AnimatePresence>
        {showEnrollModal && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            onClick={(e) => {
              if (e.target === e.currentTarget && !enrollSubmitting && !enrollSuccess) {
                setShowEnrollModal(false);
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
                    <span className="font-semibold text-gray-700">{programme.title}</span>.
                  </p>
                  <button
                    onClick={() => {
                      setShowEnrollModal(false);
                      setEnrollSuccess(false);
                      setNeedsSupport(null);
                    }}
                    className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                  >
                    Close
                  </button>
                </div>
              ) : (
                <>
                  <button
                    onClick={() => setShowEnrollModal(false)}
                    className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                  >
                    <FiX className="w-5 h-5" />
                  </button>
                  <div className="text-center mb-5">
                    <h2 className="text-base sm:text-xl font-bold text-gray-900 mb-1">
                      One more thing
                    </h2>
                    <p className="text-gray-500 text-xs sm:text-sm">
                      Enrolling in <span className="font-medium text-gray-700">{programme.title}</span>
                    </p>
                  </div>

                  <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-4">
                    Do you require any special support or accessibility assistance?
                  </h3>

                  {enrollError && (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl">
                      <p className="text-red-700 text-sm">{enrollError}</p>
                    </div>
                  )}

                  <div className="grid grid-cols-2 gap-3 mb-6">
                    <button
                      onClick={() => setNeedsSupport(true)}
                      className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${needsSupport === true
                        ? "bg-gray-900 text-white border-gray-900"
                        : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                        }`}
                    >
                      Yes, I do
                    </button>
                    <button
                      onClick={() => setNeedsSupport(false)}
                      className={`p-3 sm:p-4 rounded-xl border-2 text-sm font-medium transition-all ${needsSupport === false
                        ? "bg-gray-900 text-white border-gray-900"
                        : "bg-white border-gray-200 hover:border-yellow-400 text-gray-700"
                        }`}
                    >
                      No, thanks
                    </button>
                  </div>

                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => setShowEnrollModal(false)}
                      className="flex-1 py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium text-sm rounded-xl transition-colors"
                    >
                      Cancel
                    </button>
                    <button
                      onClick={handleEnrollSubmit}
                      disabled={needsSupport === null || enrollSubmitting}
                      className={`flex-1 py-3 font-semibold text-sm rounded-xl transition-all flex items-center justify-center gap-2 ${needsSupport !== null && !enrollSubmitting
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
    </div>
  );
};

export default ProgrammeCard; 