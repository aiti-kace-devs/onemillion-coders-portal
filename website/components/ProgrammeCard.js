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
  FiArrowLeft,
  FiChevronRight,
  FiAlertCircle,
  FiStar,
} from "react-icons/fi";
import Button from "./Button";
import {
  courseFullModalCopy,
  deriveAvailabilityIssueFromBatches,
} from "../lib/enrollmentAvailability";
// Normalizes API batch list (cohort + session order) for display; see website/lib/inPersonEnrollmentUi.js.
import { normalizeInPersonBatches, redirectToStudentDashboard } from "../lib/inPersonEnrollmentUi";
import { getAvailableBatches, getInPersonAvailableBatches, getSiblingCentres, getSiblingCourses, createBooking, submitInPersonEnrollment, setLearningMode, joinWaitlist } from "../services/api";

const IN_PERSON_DELIVERY_KEYS = new Set([
  "inperson",
  "facetoface",
  "face2face",
  "physical",
  "onsite",
  "oncampus",
  "classroom",
  "atcampus",
]);

function normalizedDeliveryKey(raw) {
  return String(raw || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z]/g, "");
}

function isInPersonDeliveryProgramme(programme) {
  if (programme?.in_person_enrollment === true) return true;
  const key = normalizedDeliveryKey(programme?.mode_of_delivery);
  if (key === "online") return false;
  return IN_PERSON_DELIVERY_KEYS.has(key);
}

function resolveEnrollmentCourseId(programme) {
  const raw = programme?.course_id ?? programme?.courseId;
  const n = Number(raw);
  if (Number.isFinite(n) && n > 0) return n;
  return null;
}

const ProgrammeCard = ({ programme, userId, centreId, token, centreIsReady = true }) => {
  const router = useRouter();
  const [showEnrollModal, setShowEnrollModal] = useState(false);
  const [needsSupport, setNeedsSupport] = useState(null);
  const [enrollSubmitting, setEnrollSubmitting] = useState(false);
  const [enrollSuccess, setEnrollSuccess] = useState(false);
  const [enrollError, setEnrollError] = useState(null);
  const [imageError, setImageError] = useState(false);
  const [imageErrors, setImageErrors] = useState({});

  // Enrollment sub-flow state
  const [enrollmentStep, setEnrollmentStep] = useState(null);
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [selectedSession, setSelectedSession] = useState(null);
  const [waitlistJoined, setWaitlistJoined] = useState(false);
  const [enrolledCourseName, setEnrolledCourseName] = useState("");
  const [enrollingCourseId, setEnrollingCourseId] = useState(null);
  const [enrollingCentreId, setEnrollingCentreId] = useState(null);
  const [enrollingCentreTitle, setEnrollingCentreTitle] = useState(null);
  const [courseFullTab, setCourseFullTab] = useState("centres");
  const [selectedBatchMonth, setSelectedBatchMonth] = useState(null);

  // API data
  const [availableBatches, setAvailableBatches] = useState([]);
  const [batchesLoading, setBatchesLoading] = useState(false);
  const [siblingCentres, setSiblingCentres] = useState([]);
  const [siblingCourses, setSiblingCourses] = useState({ matches: [], available_courses: [] });
  const [inPersonEnrollmentFlow, setInPersonEnrollmentFlow] = useState(false);
  const [inPersonMeta, setInPersonMeta] = useState(null);
  const [courseFullIssue, setCourseFullIssue] = useState(null);

  const fetchBatchesForCourse = async (courseId) => {
    try {
      setBatchesLoading(true);
      if (inPersonEnrollmentFlow) {
        const data = await getInPersonAvailableBatches(courseId, token);
        const batches = normalizeInPersonBatches(data?.batches || []);
        setAvailableBatches(batches);
        setInPersonMeta((prev) => ({
          region_name: data?.region_name ?? prev?.region_name,
          district_name: data?.district_name ?? prev?.district_name,
          certificate_title: data?.certificate_title ?? prev?.certificate_title,
          centre_title: data?.centre?.title ?? prev?.centre_title,
        }));
        return batches;
      }
      const data = await getAvailableBatches(courseId, token);
      setAvailableBatches(data?.batches || []);
      return data?.batches || [];
    } catch {
      setAvailableBatches([]);
      return [];
    } finally {
      setBatchesLoading(false);
    }
  };

  const fetchAlternatives = async (courseId, cId) => {
    try {
      const [centresData, coursesData] = await Promise.all([
        getSiblingCentres(courseId, cId, token).catch(() => ({ alternatives: [] })),
        getSiblingCourses(userId, courseId, token).catch(() => ({ matches: [], available_courses: [] })),
      ]);
      setSiblingCentres(centresData?.alternatives || []);
      setSiblingCourses({ matches: coursesData?.matches || [], available_courses: coursesData?.available_courses || [] });
    } catch { /* ignore */ }
  };

  const handleEnrollClick = () => {
    const courseId = resolveEnrollmentCourseId(programme);
    if (!courseId) {
      setEnrollError(
        "This course is missing a centre booking reference. Please refresh or choose another centre.",
      );
      return;
    }
    const inPerson = isInPersonDeliveryProgramme(programme);
    setEnrolledCourseName(programme.title);
    setEnrollingCourseId(courseId);
    setEnrollingCentreId(centreId);
    setEnrollingCentreTitle(null);
    setSelectedBatch(null);
    setSelectedSession(null);
    setNeedsSupport(null);
    setWaitlistJoined(false);
    setEnrollSuccess(false);
    setEnrollError(null);
    setSelectedBatchMonth(null);
    setCourseFullTab("centres");
    setInPersonEnrollmentFlow(inPerson);
    setInPersonMeta(null);
    setShowEnrollModal(true);

    if (inPerson) {
      setEnrollmentStep("batch");
      setBatchesLoading(true);
      (async () => {
        try {
          const data = await getInPersonAvailableBatches(courseId, token);
          const batches = normalizeInPersonBatches(data?.batches || []);
          setAvailableBatches(batches);
          setInPersonMeta({
            region_name: data?.region_name,
            district_name: data?.district_name,
            certificate_title: data?.certificate_title,
            centre_title: data?.centre?.title,
          });
          const hasAvailable = batches.some((b) =>
            b.sessions?.some((s) => Number(s.remaining) > 0),
          );
          if (!hasAvailable) {
            await fetchAlternatives(courseId, centreId);
            setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
            setEnrollmentStep("courseFull");
          }
        } catch (err) {
          setAvailableBatches([]);
          setEnrollError(
            err.response?.data?.message ||
              "Could not load enrolment availability. Please try again.",
          );
          setEnrollmentStep("batch");
        } finally {
          setBatchesLoading(false);
        }
      })();
    } else {
      setEnrollmentStep("support");
    }
  };

  const handleSupportAnswer = async (needs) => {
    setNeedsSupport(needs);
    const centreIdVal = enrollingCentreId || centreId;
    const payload = { userId, course_id: enrollingCourseId, ...(centreIdVal && { centre_id: centreIdVal }) };

    try {
      setEnrollSubmitting(true);
      setEnrollError(null);
      await setLearningMode(payload, !needs, token);

      if (!needs) {
        setEnrollSuccess(true);
      } else {
        setEnrollSubmitting(false);
        setBatchesLoading(true);
        setEnrollmentStep("batch");
        const batches = await fetchBatchesForCourse(enrollingCourseId);
        const hasAvailable = batches.some((b) =>
          b.sessions?.some((s) => Number(s.remaining) > 0),
        );
        if (!hasAvailable) {
          await fetchAlternatives(enrollingCourseId, centreIdVal);
          setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
          setEnrollmentStep("courseFull");
        }
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      setEnrollError(apiErrors ? Object.values(apiErrors).flat().join(". ") : (apiMessage || "Failed to enroll. Please try again."));
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const handleBatchSelect = (batch) => {
    const hasAvailableSession = batch.sessions?.some((s) => s.remaining > 0);
    if (!hasAvailableSession) return;
    setSelectedBatch(batch);
    setEnrollmentStep("session");
  };

  const handleSessionSelect = (session) => {
    if (session.remaining === 0) return;
    setSelectedSession(session);
    setEnrollmentStep("confirm");
  };

  const handleEnrollSubmit = async () => {
    try {
      setEnrollSubmitting(true);
      setEnrollError(null);
      const result = inPersonEnrollmentFlow
        ? await submitInPersonEnrollment(
            {
              programme_batch_id: selectedBatch.id,
              course_id: enrollingCourseId,
              course_session_id: selectedSession.session_id,
            },
            token,
          )
        : await createBooking({ programme_batch_id: selectedBatch.id, course_id: enrollingCourseId, session_id: selectedSession.session_id }, token);
      if (result.conflict) {
        const batches = await fetchBatchesForCourse(enrollingCourseId);
        const hasAvailable = batches.some((b) =>
          b.sessions?.some((s) => Number(s.remaining) > 0),
        );
        if (hasAvailable) {
          setSelectedBatch(null);
          setSelectedSession(null);
          setSelectedBatchMonth(null);
          setEnrollmentStep("batchFull");
        } else {
          await fetchAlternatives(enrollingCourseId, enrollingCentreId || centreId);
          setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
          setEnrollmentStep("courseFull");
        }
      } else if (inPersonEnrollmentFlow) {
        window.setTimeout(
          () => redirectToStudentDashboard(result.redirect_url),
          400,
        );
      } else {
        setEnrollSuccess(true);
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      setEnrollError(apiErrors ? Object.values(apiErrors).flat().join(". ") : (apiMessage || "Failed to enroll. Please try again."));
    } finally { setEnrollSubmitting(false); }
  };

  const handleJoinWaitlist = async () => {
    try {
      setEnrollSubmitting(true);
      await joinWaitlist(userId, enrollingCourseId, token);
      setWaitlistJoined(true);
    } catch (err) {
      const apiMessage = err.response?.data?.message;
      setEnrollError(apiMessage || "Failed to join waitlist. Please try again.");
    } finally { setEnrollSubmitting(false); }
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
    setAvailableBatches([]);
    setSiblingCentres([]);
    setSiblingCourses({ matches: [], available_courses: [] });
    setSelectedBatchMonth(null);
    setCourseFullTab("centres");
    setInPersonEnrollmentFlow(false);
    setInPersonMeta(null);
    setCourseFullIssue(null);
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
    const steps = [{ label: "Batch", step: "batch" }, { label: "Session", step: "session" }, { label: "Confirm", step: "confirm" }];
    return (
      <div className="flex flex-wrap items-center gap-x-1.5 gap-y-2 mb-5">
        {steps.map(({ label, step: s }, i) => {
          const isDone = i < current;
          const isCurrent = i === current;
          const canClick = isDone;
          return (
            <React.Fragment key={label}>
              <button disabled={!canClick} onClick={() => canClick && setEnrollmentStep(s)} className={`flex items-center gap-1.5 shrink-0 ${isDone ? "text-green-500 cursor-pointer" : isCurrent ? "text-yellow-600" : "text-gray-300"} ${canClick ? "hover:opacity-80" : ""}`}>
                <div className={`w-6 h-6 sm:w-7 sm:h-7 rounded-full text-[11px] sm:text-xs font-bold flex items-center justify-center ${isDone ? "bg-green-500 text-white" : isCurrent ? "bg-yellow-400 text-gray-900" : "bg-gray-100 text-gray-400"}`}>{isDone ? <FiCheckCircle className="w-3.5 h-3.5 sm:w-4 sm:h-4" /> : i + 1}</div>
                <span className="text-xs sm:text-sm font-medium">{label}</span>
              </button>
              {i < 2 && <div className={`hidden sm:block flex-1 min-w-[12px] h-px ${isDone ? "bg-green-300" : "bg-gray-200"}`} />}
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
          {userId ? "Enroll Now" : "Read More"}
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
              className="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-xl px-5 py-6 sm:p-8 relative max-h-[85vh] sm:max-h-[90vh] overflow-y-auto"
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
                      {selectedBatch.batch || `Batch ${selectedBatch.id}`} · {selectedSession.session_name} (
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
                        How would you like to study?
                      </h2>
                      <p className="text-gray-400 text-[11px] sm:text-sm line-clamp-1">
                        Enrolling in{" "}
                        <span className="text-gray-600">
                          {enrolledCourseName}
                        </span>
                      </p>
                    </div>
                    {enrollError && (
                      <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{enrollError}</p>
                      </div>
                    )}
                    <div className="space-y-2.5 mb-3">
                      {centreIsReady && (
                        <button
                          onClick={() => handleSupportAnswer(true)}
                          disabled={enrollSubmitting}
                          className="w-full p-3.5 sm:p-4 rounded-xl border-2 text-left transition-all active:scale-[0.99] bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md group disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-50 to-orange-50 flex items-center justify-center flex-shrink-0 group-hover:from-yellow-100 group-hover:to-orange-100 transition-colors">
                              <FiMapPin className="w-4 h-4 text-yellow-600" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors">Study at a Centre</div>
                              <div className="text-[11px] sm:text-xs text-gray-500 mt-0.5 leading-snug">Laptop and internet access provided at our support centre, with staff on hand</div>
                            </div>
                            <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                          </div>
                        </button>
                      )}
                      <button
                        onClick={() => handleSupportAnswer(false)}
                        disabled={enrollSubmitting}
                        className="w-full p-3.5 sm:p-4 rounded-xl border-2 text-left transition-all active:scale-[0.99] bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md group disabled:opacity-60 disabled:cursor-not-allowed"
                      >
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center flex-shrink-0 group-hover:from-blue-100 group-hover:to-indigo-100 transition-colors">
                            {enrollSubmitting ? <FiLoader className="w-4 h-4 text-blue-600 animate-spin" /> : <FiMonitor className="w-4 h-4 text-blue-600" />}
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors">Study from Home</div>
                            <div className="text-[11px] sm:text-xs text-gray-500 mt-0.5 leading-snug">Complete your course entirely online using your own device, at your own pace</div>
                          </div>
                          <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                        </div>
                      </button>
                    </div>
                    {!centreIsReady && (
                      <p className="text-[11px] text-gray-400 text-center mb-3">Centre-based study is not yet available at this centre</p>
                    )}
                    <button onClick={closeEnrollmentModal} className="w-full py-2.5 text-sm text-gray-400 hover:text-gray-600 font-medium transition-colors">Cancel</button>
                  </>
                )}

              {/* Batch Selection with Month Tabs */}
              {!enrollSuccess && !waitlistJoined && enrollmentStep === "batch" && (() => {
                const monthMap = {};
                availableBatches.forEach((b) => { const key = b.start_date.slice(0, 7); if (!monthMap[key]) monthMap[key] = []; monthMap[key].push(b); });
                const months = Object.keys(monthMap).sort();
                const activeMonth = selectedBatchMonth || months[0];
                const filteredBatches = monthMap[activeMonth] || [];
                const batchTotalRemaining = (b) => (b.sessions || []).reduce((sum, s) => sum + s.remaining, 0);
                return (
                  <>
                    <button onClick={closeEnrollmentModal} className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"><FiX className="w-4.5 h-4.5" /></button>
                    <div className="flex justify-center mb-3 sm:hidden"><div className="w-8 h-1 bg-gray-200 rounded-full" /></div>
                    <StepIndicator current={0} />
                    <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-0.5">When would you like to start?</h2>
                    <p className="text-gray-500 text-sm sm:text-base mb-4 line-clamp-2 leading-snug">{enrolledCourseName}</p>
                    {enrollError && (
                      <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{enrollError}</p>
                      </div>
                    )}

                    {batchesLoading ? (
                      <div className="flex items-center justify-center py-12"><FiLoader className="w-5 h-5 text-yellow-500 animate-spin" /></div>
                    ) : (
                      <>
                        {months.length > 1 && (
                          <div className="flex gap-1.5 overflow-x-auto scrollbar-hide pb-1 mb-4 -mx-1 px-1">
                            {months.map((m) => {
                              const isActive = m === activeMonth;
                              const label = new Date(m + "-01").toLocaleDateString("en-GB", { month: "short" });
                              return (
                                <button key={m} onClick={() => setSelectedBatchMonth(m)}
                                  className={`flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 ${isActive ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700 hover:bg-gray-200"}`}>
                                  {label}
                                </button>
                              );
                            })}
                          </div>
                        )}
                        <div className="space-y-2">
                          {filteredBatches.map((batch) => {
                            const totalRemaining = batchTotalRemaining(batch);
                            const isFull = totalRemaining === 0;
                            const startStr = new Date(batch.start_date).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
                            const endStr = new Date(batch.end_date).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
                            return (
                              <button key={batch.id} onClick={() => handleBatchSelect(batch)} disabled={isFull}
                                className={`w-full text-left p-3 sm:p-4 rounded-xl border transition-all duration-200 group ${isFull ? "bg-gray-50/80 border-gray-100 cursor-not-allowed" : "bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.99]"}`}>
                                <div className="flex items-center gap-3">
                                  <div className={`w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0 ${isFull ? "bg-gray-100" : "bg-gradient-to-br from-yellow-50 to-orange-50 group-hover:from-yellow-100 group-hover:to-orange-100"} transition-colors`}>
                                    <span className={`text-[10px] font-medium leading-none ${isFull ? "text-gray-400" : "text-yellow-700"}`}>{new Date(batch.start_date).toLocaleDateString("en-GB", { month: "short" })}</span>
                                    <span className={`text-base font-bold leading-tight ${isFull ? "text-gray-400" : "text-gray-900"}`}>{new Date(batch.start_date).getDate()}</span>
                                  </div>
                                  <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-0.5">
                                      <span className={`text-sm font-semibold ${isFull ? "text-gray-400" : "text-gray-900 group-hover:text-yellow-700"} transition-colors`}>{batch.batch || `Batch ${batch.id}`}</span>
                                      {isFull && <span className="px-1.5 py-0.5 bg-red-50 text-red-500 text-[10px] font-medium rounded-full">Full</span>}
                                    </div>
                                    <div className={`text-[11px] sm:text-xs ${isFull ? "text-gray-300" : "text-gray-500"} mb-1`}>{startStr} — {endStr}</div>
                                    <div className="text-[10px] text-gray-400">{(batch.sessions || []).filter((s) => s.remaining > 0).length} of {(batch.sessions || []).length} sessions available</div>
                                  </div>
                                  {!isFull && <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />}
                                </div>
                              </button>
                            );
                          })}
                          {filteredBatches.length === 0 && <div className="text-center py-8 text-gray-400 text-sm">No batches this month</div>}
                        </div>
                      </>
                    )}
                  </>
                );
              })()}

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
                    <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">
                      Choose your session
                    </h2>
                    <div className="mb-4 px-2.5 py-2 bg-gradient-to-r from-yellow-50/80 to-transparent rounded-lg inline-flex items-center gap-1.5 text-xs sm:text-sm">
                      <FiCalendar className="w-3 h-3 text-yellow-600 flex-shrink-0" />
                      <span className="font-medium text-gray-600">
                        {selectedBatch?.batch || `Batch ${selectedBatch?.id}`}
                      </span>
                    </div>
                    <div className="space-y-2">
                      {(selectedBatch?.sessions || []).map((session) => {
                        const isSelected = selectedSession?.session_id === session.session_id;
                        const isFull = session.remaining === 0;
                        return (
                          <button key={session.session_id} onClick={() => handleSessionSelect(session)} disabled={isFull}
                            className={`w-full text-left p-3 sm:p-4 rounded-xl border transition-all duration-200 group active:scale-[0.99] ${isFull ? "bg-gray-50/80 border-gray-100 cursor-not-allowed opacity-50" : isSelected ? "bg-gray-900 text-white border-gray-900 shadow-lg" : "bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md"}`}>
                            <div className="flex items-center gap-3">
                              <div className={`w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors ${isSelected ? "bg-yellow-400" : isFull ? "bg-gray-100" : "bg-gradient-to-br from-yellow-50 to-orange-50 group-hover:from-yellow-100 group-hover:to-orange-100"}`}>
                                <FiClock className={`w-4 h-4 ${isSelected ? "text-gray-900" : isFull ? "text-gray-400" : "text-yellow-600"}`} />
                              </div>
                              <div className="flex-1 min-w-0">
                                <div className="text-sm sm:text-base font-semibold break-words">{session.session_name}</div>
                                <div className={`text-xs sm:text-sm mt-0.5 ${isSelected ? "text-gray-300" : "text-gray-600"}`}>{session.time}</div>
                              </div>
                              <div className="flex items-center gap-1.5 flex-shrink-0">
                                {isFull ? (
                                  <span className="text-[10px] text-red-500 font-medium">Full</span>
                                ) : session.show_seat_count ? (
                                  <span className={`text-[10px] tabular-nums ${isSelected ? "text-yellow-400" : session.remaining <= 5 ? "text-orange-600 font-medium" : "text-gray-400"}`}>{session.remaining} left</span>
                                ) : null}
                                {!isFull && !isSelected && <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" />}
                              </div>
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
                    <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">
                      Confirm enrollment
                    </h2>
                    {(() => {
                      const regionDistrict = [inPersonMeta?.region_name, inPersonMeta?.district_name].filter(Boolean).join(" → ");
                      const centreName = inPersonMeta?.centre_title?.trim() || "";
                      const awardTitle =
                        (inPersonMeta?.certificate_title && String(inPersonMeta.certificate_title).trim()) ||
                        programme?.course_certification?.[0]?.title ||
                        programme?.courseCertification?.[0]?.title ||
                        "";
                      return (
                    <div className="mb-5 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100/50 border border-gray-200 overflow-hidden">
                      <div className="px-3 sm:px-4 py-3 border-b border-gray-200/60">
                        <p className="text-xs sm:text-sm font-medium text-gray-500 mb-1">Course</p>
                        <h3 className="text-sm sm:text-base font-semibold text-gray-900 break-words leading-snug">
                          {enrolledCourseName}
                        </h3>
                      </div>
                      <dl className="px-3 sm:px-4 py-3 space-y-3 text-sm sm:text-base">
                        <div>
                          <dt className="text-xs sm:text-sm font-medium text-gray-500">Region and district</dt>
                          <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">{regionDistrict || "—"}</dd>
                        </div>
                        <div>
                          <dt className="text-xs sm:text-sm font-medium text-gray-500">Centre</dt>
                          <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">{centreName || "—"}</dd>
                        </div>
                        <div>
                          <dt className="text-xs sm:text-sm font-medium text-gray-500">Award on completion</dt>
                          <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">{awardTitle || "—"}</dd>
                        </div>
                        <div className="pt-1 border-t border-gray-200/70">
                          <dt className="text-xs sm:text-sm font-medium text-gray-500">Batch</dt>
                          <dd className="mt-1 font-semibold text-gray-900 break-words">
                            {selectedBatch?.batch || `Batch ${selectedBatch?.id}`}
                            {selectedBatch?.start_date && (
                              <> · {new Date(selectedBatch.start_date).toLocaleDateString("en-GB", { month: "short", year: "numeric" })}</>
                            )}
                          </dd>
                        </div>
                        <div>
                          <dt className="text-xs sm:text-sm font-medium text-gray-500">Session</dt>
                          <dd className="mt-1 font-semibold text-gray-900 break-words">
                            {selectedSession?.session_name} · {selectedSession?.time}
                          </dd>
                        </div>
                      </dl>
                    </div>
                      );
                    })()}
                    {enrollError && (
                      <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <p className="text-red-700 text-sm">{enrollError}</p>
                      </div>
                    )}
                    <div className="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2.5">
                      <button
                        onClick={closeEnrollmentModal}
                        className="flex-1 py-3.5 sm:py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold text-sm sm:text-base rounded-xl transition-colors"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={handleEnrollSubmit}
                        disabled={enrollSubmitting}
                        className={`flex-1 py-3.5 sm:py-3 font-semibold text-sm sm:text-base rounded-xl transition-all flex items-center justify-center gap-2 active:scale-[0.97] ${!enrollSubmitting ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900 shadow-sm" : "bg-gray-100 text-gray-400 cursor-not-allowed"}`}
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
                enrollmentStep === "courseFull" && (() => {
                  const fullCopy = courseFullModalCopy(courseFullIssue);
                  return (
                  <>
                    <div className="flex justify-center mb-3 sm:hidden"><div className="w-8 h-1 bg-gray-200 rounded-full" /></div>
                    <button onClick={closeEnrollmentModal} className="absolute top-3 right-3 z-10 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"><FiX className="w-4.5 h-4.5" /></button>

                    <div className="text-center mb-5">
                      <div className="w-11 h-11 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center mx-auto mb-3"><FiAlertCircle className="w-5 h-5 text-yellow-700" /></div>
                      <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-1.5">{fullCopy.title}</h2>
                      <p className="text-sm sm:text-base font-semibold text-gray-700 mb-1">{enrolledCourseName}</p>
                      <p className="text-xs sm:text-sm text-gray-600 mb-2 max-w-md mx-auto leading-relaxed px-1">{fullCopy.detail}</p>
                      <div className="flex items-center justify-center gap-1.5 text-xs sm:text-sm text-gray-700"><FiMapPin className="w-3 h-3 flex-shrink-0" /><span>{centreTitle}</span></div>
                    </div>

                    <div className="flex bg-gray-200/70 rounded-xl p-1 mb-5">
                      <button onClick={() => setCourseFullTab("centres")} className={`flex-1 py-3 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all text-center ${courseFullTab === "centres" ? "bg-white text-gray-900 shadow-md ring-1 ring-gray-200/50" : "text-gray-500 hover:text-gray-800"}`}>Find another centre</button>
                      <button onClick={() => setCourseFullTab("courses")} className={`flex-1 py-3 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all text-center ${courseFullTab === "courses" ? "bg-white text-gray-900 shadow-md ring-1 ring-gray-200/50" : "text-gray-500 hover:text-gray-800"}`}>Explore other courses</button>
                    </div>

                    <div className="mb-5 min-h-[140px]">
                      <AnimatePresence mode="wait" initial={false}>
                      {courseFullTab === "centres" && (
                        <motion.div key="centres-tab" initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 10 }} transition={{ duration: 0.2 }}>
                          <h4 className="text-[10px] sm:text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-3">Available nearby</h4>
                          <div className="space-y-2">
                            {siblingCentres.map((alt, idx) => (
                              <motion.button key={alt.centre_id} initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.2, delay: idx * 0.05 }}
                                onClick={() => { setEnrollingCentreId(alt.centre_id); setEnrollingCentreTitle(alt.centre_name); setEnrollingCourseId(alt.course_id || enrollingCourseId); setSelectedBatch(null); setSelectedSession(null); setSelectedBatchMonth(null); setCourseFullTab("centres"); fetchBatchesForCourse(alt.course_id || enrollingCourseId).then(() => setEnrollmentStep("batch")); }}
                                className="w-full text-left p-3 sm:p-4 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98]">
                                <div className="flex items-center justify-between gap-3">
                                  <div className="min-w-0">
                                    <div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors truncate">{alt.centre_name}</div>
                                    <div className="text-xs text-green-600 font-medium mt-0.5">{alt.available} slots available</div>
                                  </div>
                                  <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                                </div>
                              </motion.button>
                            ))}
                          </div>
                        </motion.div>
                      )}
                      {courseFullTab === "courses" && (
                        <motion.div key="courses-tab" initial={{ opacity: 0, x: 10 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -10 }} transition={{ duration: 0.2 }}>
                          <h4 className="text-[10px] sm:text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-3">Available here</h4>
                          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            {[...(siblingCourses.matches || []), ...(siblingCourses.available_courses || [])].slice(0, 6).map((alt) => (
                              <button key={alt.course_id || alt.id} onClick={() => { setEnrolledCourseName(alt.title); setEnrollingCourseId(alt.course_id || alt.id); setEnrollingCentreId(alt.centre_id || enrollingCentreId); setSelectedBatch(null); setSelectedSession(null); setSelectedBatchMonth(null); setCourseFullTab("centres"); fetchBatchesForCourse(alt.course_id || alt.id).then(() => setEnrollmentStep("batch")); }}
                                className="text-left rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] overflow-hidden">
                                <div className="relative h-20 sm:h-24 bg-gray-100">
                                  {alt.image && !imageErrors[`alt-${alt.course_id || alt.id}`] ? (
                                    <Image src={alt.image} alt={alt.title} fill className="object-cover" onError={() => setImageErrors((prev) => ({ ...prev, [`alt-${alt.course_id || alt.id}`]: true }))} />
                                  ) : (
                                    <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center"><Image src="/images/one-million-coders-logo.png" alt="One Million Coders" width={60} height={20} className="opacity-15" /></div>
                                  )}
                                  <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
                                  <div className="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-between">
                                    <span className={`px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold rounded-full backdrop-blur-sm ${(alt.slot_left || 0) > 0 ? "bg-green-500/90 text-white" : "bg-red-500/90 text-white"}`}>{(alt.slot_left || 0) > 0 ? `${alt.slot_left} slots` : "Full"}</span>
                                    {alt.match_percentage && <span className="flex items-center gap-0.5 px-1.5 py-0.5 bg-white/90 text-[9px] sm:text-[10px] font-medium rounded-full backdrop-blur-sm text-yellow-700"><FiStar className="w-2.5 h-2.5" />{alt.match_percentage}</span>}
                                  </div>
                                </div>
                                <div className="p-2 sm:p-2.5">
                                  <h4 className="text-[11px] sm:text-xs font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors line-clamp-2 leading-tight mb-1">{alt.title}</h4>
                                  <div className="flex items-center gap-1 text-[10px] text-gray-400"><FiClock className="w-2.5 h-2.5" /><span>{alt.duration}</span></div>
                                </div>
                              </button>
                            ))}
                          </div>
                        </motion.div>
                      )}
                      </AnimatePresence>
                    </div>

                    <div className="pt-4 border-t border-gray-100">
                      <h4 className="text-[10px] sm:text-[11px] font-bold text-gray-700 uppercase tracking-widest text-center mb-3">Or</h4>
                      <div className="grid grid-cols-2 gap-2">
                        <button onClick={() => handleSupportAnswer(false)} disabled={enrollSubmitting} className="p-3 sm:p-4 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] text-center">
                          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 flex items-center justify-center mx-auto mb-2 group-hover:from-green-100 group-hover:to-emerald-100 transition-colors">
                            {enrollSubmitting ? <FiLoader className="w-4 h-4 text-green-600 animate-spin" /> : <FiCheckCircle className="w-4 h-4 text-green-600" />}
                          </div>
                          <div className="text-xs sm:text-sm font-semibold text-gray-900 mb-0.5">Enroll without support</div>
                          <div className="text-[10px] sm:text-[11px] text-gray-400 leading-tight">Skip support, enroll now</div>
                        </button>
                        <button onClick={handleJoinWaitlist} className="p-3 sm:p-4 rounded-xl bg-white border border-dashed border-gray-300 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] text-center">
                          <div className="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center mx-auto mb-2 group-hover:bg-yellow-100 transition-colors"><FiClock className="w-4 h-4 text-yellow-600" /></div>
                          <div className="text-xs sm:text-sm font-semibold text-gray-900 mb-0.5">Join the waitlist</div>
                          <div className="text-[10px] sm:text-[11px] text-gray-400 leading-tight">Get notified when available</div>
                        </button>
                      </div>
                    </div>
                  </>
                  );
                })()}

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
                      {siblingCentres.map((alt) => (
                        <button
                          key={alt.centre_id}
                          onClick={() => {
                            setEnrollingCentreId(alt.centre_id);
                            setEnrollingCentreTitle(alt.centre_name);
                            setEnrollingCourseId(alt.course_id || enrollingCourseId);
                            setSelectedBatch(null);
                            setSelectedSession(null);
                            setSelectedBatchMonth(null);
                            fetchBatchesForCourse(alt.course_id || enrollingCourseId).then(() => setEnrollmentStep("batch"));
                          }}
                          className="w-full text-left p-3 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]"
                        >
                          <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                              <FiMapPin className="w-4 h-4 text-blue-600" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <div className="text-sm font-medium text-gray-900 group-hover:text-yellow-700 transition-colors truncate">
                                {alt.centre_name}
                              </div>
                            </div>
                            <div className="flex items-center gap-1.5 flex-shrink-0">
                              <span className="text-xs font-bold text-green-600">
                                {alt.available} slots
                              </span>
                              <FiChevronRight className="w-3.5 h-3.5 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" />
                            </div>
                          </div>
                        </button>
                      ))}
                    </div>
                    <div className="pt-3 border-t border-gray-100">
                      <button
                        onClick={handleJoinWaitlist}
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
