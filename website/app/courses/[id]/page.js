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
  FiInfo,
  FiMonitor,
  FiUsers,
  FiCalendar,
  FiSun,
} from "react-icons/fi";
import {
  getAllRegions,
  getDistrictsByBranch,
  getCentresByDistrict,
} from "../../../services/pages";
import {
  checkUserStatus,
  getCourseMatchQuestions,
  getCourseRecommendations,
  checkUserRecommendedCourses,
  getAvailableBatches,
  getInPersonAvailableBatches,
  getSiblingCentres,
  getSiblingCourses,
  createBooking,
  setLearningMode,
  joinWaitlist,
} from "../../../services/api";
import Button from "../../../components/Button";
import {
  courseFullModalCopy,
  deriveAvailabilityIssueFromBatches,
} from "../../../lib/enrollmentAvailability";
// In-person batches: same ordering/summary fields as ProgrammeCard (lib/inPersonEnrollmentUi.js).
import { normalizeInPersonBatches, redirectToStudentDashboard } from "../../../lib/inPersonEnrollmentUi";

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

/** Matches ProgrammeCard: online opens study mode first; in-person goes straight to batch/session. */
function isInPersonDeliveryCourse(course) {
  if (course?.in_person_enrollment === true) return true;
  const key = normalizedDeliveryKey(course?.mode_of_delivery);
  if (key === "online") return false;
  return IN_PERSON_DELIVERY_KEYS.has(key);
}

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

  // Enrollment sub-flow state: studyMode → batch → session → confirm (online); courseFull uses handleSupportAnswer(false) only
  const [enrollmentStep, setEnrollmentStep] = useState(null); // "studyMode" | "selfPacedCohort" | "selfPacedConfirm" | "batch" | "session" | "confirm" | "courseFull" | "batchFull"
  const [studyModeChoice, setStudyModeChoice] = useState(null); // null | "centre" | "home"
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [selectedSession, setSelectedSession] = useState(null);
  const [waitlistJoined, setWaitlistJoined] = useState(false);
  const [courseFullTab, setCourseFullTab] = useState("centres");
  const [courseFullIssue, setCourseFullIssue] = useState(null);
  const [selectedBatchMonth, setSelectedBatchMonth] = useState(null);
  const isDemo = searchParams.get("demo") === "true";

  // Course picker must run inside the student portal iframe (not standalone).
  useEffect(() => {
    if (isDemo) return;
    if (searchParams.get("embed") === "true") return;
    let isTopLevel = false;
    try {
      isTopLevel = window.self === window.top;
    } catch {
      isTopLevel = false;
    }
    if (isTopLevel) {
      window.document.body.innerHTML =
        '<p style="margin:2rem;font-family:system-ui,sans-serif">Please access this page from the student portal.</p>';
    }
  }, [isDemo, searchParams]);

  // Availability data (fetched from API)
  const [availableBatches, setAvailableBatches] = useState([]);
  const [batchesLoading, setBatchesLoading] = useState(false);
  const [siblingCentres, setSiblingCentres] = useState([]);
  const [siblingCourses, setSiblingCourses] = useState({ matches: [], available_courses: [] });
  const [inPersonEnrollmentFlow, setInPersonEnrollmentFlow] = useState(false);
  const [inPersonMeta, setInPersonMeta] = useState(null);
  const [enrollingCourseRecord, setEnrollingCourseRecord] = useState(null);
  const [enrollmentSuccessRedirectUrl, setEnrollmentSuccessRedirectUrl] = useState(null);

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

  // Sync step and selections to query params
  useEffect(() => {
    if (!userStatus || step <= 1) return;
    const hasAnswers = Object.keys(answers).length > 0;
    const hasQuizState = hasAnswers || currentQuestion > 0 || showResults;
    updateQueryParams({
      step,
      region: selectedRegion?.id || null,
      district: selectedDistrict?.id || null,
      centre: selectedCentre?.id || null,
      q: step === 4 && hasQuizState ? currentQuestion : null,
      a: step === 4 && hasAnswers ? JSON.stringify(answers) : null,
      results: step === 4 && showResults ? "1" : null,
    });
  }, [
    step,
    selectedRegion,
    selectedDistrict,
    selectedCentre,
    userStatus,
    currentQuestion,
    answers,
    showResults,
    updateQueryParams,
  ]);

  // Restore progress from query params
  const restoreFromParams = async () => {
    const savedStep = parseInt(searchParams.get("step"));
    const regionId = searchParams.get("region");
    const districtId = searchParams.get("district");
    const centreId = searchParams.get("centre");
    const savedQuestion = parseInt(searchParams.get("q"));
    const savedAnswersRaw = searchParams.get("a");
    const savedResults = searchParams.get("results") === "1";

    if (!savedStep || savedStep <= 1 || !regionId) return;

    try {
      // Fetch regions and find the saved one
      const regions = await getAllRegions(token);
      setAllRegions(regions);
      const region = regions?.find((r) => String(r.id) === regionId);
      if (!region) return;
      setSelectedRegion(region);

      if (savedStep >= 2) {
        const districts = await getDistrictsByBranch(region.id, token);
        setAvailableDistricts(districts);

        if (districtId && savedStep >= 3) {
          const district = districts?.districts?.find(
            (d) => String(d.id) === districtId,
          );
          if (!district) {
            setStep(2);
            return;
          }
          setSelectedDistrict(district);

          const centres = await getCentresByDistrict(district.id, token);
          setAvailableCenters(centres);

          if (centreId && savedStep >= 4) {
            const centre = centres?.centres?.find(
              (c) => String(c.id) === centreId,
            );
            if (!centre) {
              setStep(3);
              return;
            }
            setSelectedCentre(centre);

            const data = await getCourseMatchQuestions("Choice", token);
            setQuestions(data || []);

            // Restore quiz progress
            let parsedAnswers = {};
            if (savedAnswersRaw) {
              try {
                parsedAnswers = JSON.parse(savedAnswersRaw);
                setAnswers(parsedAnswers);
              } catch {
                parsedAnswers = {};
              }
            }
            if (!Number.isNaN(savedQuestion)) {
              setCurrentQuestion(Math.max(0, savedQuestion));
            }

            // Restore results view — regenerate recommendations from stored answers
            if (savedResults && Object.keys(parsedAnswers).length > 0) {
              try {
                setSubmitting(true);
                const optionIds = Object.values(parsedAnswers).flat();
                const recs = await getCourseRecommendations({
                  optionIds,
                  userId: id,
                  regionId: region.id,
                  centreId: centre.id,
                  token,
                });
                setRecommendations(recs || []);
                setShowResults(true);
              } catch {
                // If regenerate fails, fall back to quiz view
              } finally {
                setSubmitting(false);
              }
            }
          }
        }
      }

      setStep(savedStep);
    } catch {
      // If restore fails, user starts fresh
    }
  };

  useEffect(() => {
    // DEMO MODE: Skip verification, use mock data for testing UI
    if (isDemo) {
      setUserStatus({
        success: true,
        name: "Demo User",
        email: "demo@test.com",
      });
      setVerifying(false);
      setCheckingRecommendations(false);
      fetchAllRegions();
      return;
    }

    const verifyUser = async () => {
      try {
        setVerifying(true);
        setVerificationError(null);
        const data = await checkUserStatus(id, token);
        if (data?.success === false) {
          setVerificationError(
            data.message || "User not found. Please register first.",
          );
          setCheckingRecommendations(false);
          return;
        }
        setUserStatus(data);

        // URL state takes priority — an active session beats previously-saved recommendations
        const hasProgress = searchParams.get("step");
        if (hasProgress) {
          setCheckingRecommendations(true);
          try {
            await restoreFromParams();
          } finally {
            setCheckingRecommendations(false);
          }
          return;
        }

        // No active session — check for previous recommended courses
        try {
          setCheckingRecommendations(true);
          const recData = await checkUserRecommendedCourses(id, token);
          if (recData?.success && recData?.matches?.length > 0) {
            setPreviousRecommendations(recData);
          } else {
            fetchAllRegions();
          }
        } catch {
          fetchAllRegions();
        } finally {
          setCheckingRecommendations(false);
        }
      } catch (err) {
        console.error("Error verifying user:", err);
        setVerificationError(
          err.response?.status === 404
            ? "User not found. Please register first."
            : "Unable to verify your account. Please try again.",
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

  const fetchDistricts = useCallback(
    async (branchId) => {
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
    },
    [token],
  );

  const fetchCenters = useCallback(
    async (districtId) => {
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
    },
    [token],
  );

  const fetchQuestions = useCallback(async () => {
    if (isDemo) {
      setLoading(true);
      await new Promise((r) => setTimeout(r, 300));
      setQuestions([
        {
          id: 1,
          question: "What is your experience level with technology?",
          tag: "experience",
          is_multiple_select: false,
          course_match_options: [
            { id: 1, answer: "Complete beginner" },
            { id: 2, answer: "Some experience" },
            { id: 3, answer: "Intermediate" },
            { id: 4, answer: "Advanced" },
          ],
        },
        {
          id: 2,
          question: "How much time can you commit per week?",
          tag: "timeCommitment",
          is_multiple_select: false,
          course_match_options: [
            { id: 5, answer: "Less than 10 hours" },
            { id: 6, answer: "10-20 hours" },
            { id: 7, answer: "20-40 hours" },
          ],
        },
        {
          id: 3,
          question: "What area interests you the most?",
          tag: "interest",
          is_multiple_select: false,
          course_match_options: [
            { id: 8, answer: "Cybersecurity" },
            { id: 9, answer: "Web Development" },
            { id: 10, answer: "Data Science" },
            { id: 11, answer: "Mobile Development" },
          ],
        },
      ]);
      setLoading(false);
      return;
    }
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
  }, [token, isDemo]);

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
    if (isDemo) {
      setSubmitting(true);
      await new Promise((r) => setTimeout(r, 600));
      setRecommendations([
        {
          id: 1,
          title: "Cybersecurity Fundamentals",
          sub_title: "Network security and ethical hacking",
          duration: "120 Hours",
          mode_of_delivery: "In Person",
          match_percentage: "95%",
          image: "/images/courses/cybersecuirty-officer.jpg",
        },
        {
          id: 2,
          title: "Web Application Development",
          sub_title: "Full-stack web development",
          duration: "200 Hours",
          mode_of_delivery: "Online",
          match_percentage: "88%",
          image: "/images/courses/data-protection-manager.jpg",
        },
        {
          id: 3,
          title: "Data Analytics with Python",
          sub_title: "Data analysis and visualization",
          duration: "80 Hours",
          mode_of_delivery: "In Person",
          match_percentage: "82%",
          image: "/images/courses/data-analyst.jpg",
        },
        {
          id: 4,
          title: "Mobile App Development",
          sub_title: "Build Android and iOS apps",
          duration: "160 Hours",
          mode_of_delivery: "In Person",
          match_percentage: "75%",
          image: "/images/courses/dpo.JPG",
        },
      ]);
      setShowResults(true);
      setSubmitting(false);
      return;
    }
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
    setEnrollmentStep(null);
    setEnrollSuccess(false);
  };

  const handleStartQuizFlow = () => {
    setPreviousRecommendations(null);
    fetchAllRegions();
  };

  const getPreferredProgrammeMode = () => {
    const selectedOptionIds = new Set(
      Object.values(answers)
        .flat()
        .map((optionId) => Number(optionId))
        .filter(Boolean),
    );

    if (selectedOptionIds.size === 0) {
      return null;
    }

    for (const question of questions) {
      for (const option of question.course_match_options || []) {
        if (!selectedOptionIds.has(Number(option.id))) {
          continue;
        }

        const rawValue = String(option.value || option.answer || "")
          .trim()
          .toLowerCase()
          .replace(/[^a-z]/g, "");

        if (rawValue === "online" || rawValue === "onilne") {
          return "Online";
        }

        if (rawValue === "inperson") {
          return "In Person";
        }
      }
    }

    return null;
  };

  const handleViewAllCourses = () => {
    const params = new URLSearchParams({
      user_id: id,
    });

    if (selectedCentre?.id) {
      params.set("centre_id", String(selectedCentre.id));
    }

    const preferredMode = getPreferredProgrammeMode();
    if (preferredMode) {
      params.set("mode", preferredMode);
    }

    if (token) {
      params.set("token", token);
    }

    router.push(`/programmes?${params.toString()}`);
  };

  // Click "Enroll Now" → study mode (online) or batch flow (in-person)
  // Fetch batches from API for a course
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
      const batches = normalizeInPersonBatches(data?.batches || []);
      setAvailableBatches(batches);
      setInPersonMeta((prev) => ({
        region_name: data?.region_name ?? prev?.region_name,
        district_name: data?.district_name ?? prev?.district_name,
        certificate_title: data?.certificate_title ?? prev?.certificate_title,
        centre_title: data?.centre?.title ?? prev?.centre_title,
      }));
      return batches;
    } catch (err) {
      console.error("Failed to fetch batches:", err);
      setAvailableBatches([]);
      return [];
    } finally {
      setBatchesLoading(false);
    }
  };

  // Fetch alternatives when centre is full
  const fetchAlternatives = async (courseId, centreId) => {
    try {
      const [centresData, coursesData] = await Promise.all([
        getSiblingCentres(courseId, centreId, token).catch(() => ({ alternatives: [] })),
        getSiblingCourses(id, courseId, token).catch(() => ({ matches: [], available_courses: [] })),
      ]);
      setSiblingCentres(centresData?.alternatives || []);
      setSiblingCourses({ matches: coursesData?.matches || [], available_courses: coursesData?.available_courses || [] });
    } catch (err) {
      console.error("Failed to fetch alternatives:", err);
    }
  };

  // Click "Enroll Now" → online: study mode first; in-person: batch/session (same as ProgrammeCard)
  const handleEnrollClick = (course) => {
    const courseId = course.course_id || course.id;
    const centreId = course.centre_id || selectedCentre?.id;
    setEnrolledCourseName(course.title);
    setEnrollingCourseId(courseId);
    setEnrollingCentreId(centreId);
    setSelectedBatch(null);
    setSelectedSession(null);
    setNeedsSupport(null);
    setWaitlistJoined(false);
    setSelectedBatchMonth(null);
    setCourseFullTab("centres");

    // For users coming in via previously-recommended courses, selectedCentre
    // is not populated by the region/district/centre picker flow. Hydrate it
    // from the centre attached to this specific recommended course so the
    // study-mode modal can read is_ready correctly.
    if (course.centre) {
      setSelectedCentre(course.centre);
    }

    const inPerson = isInPersonDeliveryCourse(course);
    setInPersonEnrollmentFlow(inPerson);
    setEnrollingCourseRecord(course || null);
    setEnrollmentSuccessRedirectUrl(null);

    if (inPerson) {
      setStudyModeChoice(null);
      setEnrollmentStep("batch");
      setBatchesLoading(true);
      (async () => {
        try {
          setError(null);
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
            (b.sessions || []).some((s) => Number(s.remaining) > 0),
          );
          if (!hasAvailable) {
            await fetchAlternatives(courseId, centreId);
            setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
            setEnrollmentStep("courseFull");
          }
        } catch (err) {
          setAvailableBatches([]);
          const apiMessage = err.response?.data?.message;
          setError(
            apiMessage || "Could not load enrolment availability. Please try again.",
          );
          setEnrollmentStep("batch");
        } finally {
          setBatchesLoading(false);
        }
      })();
    } else {
      setStudyModeChoice(null);
      // Always open study mode first; "Study at a Centre" is shown only when is_ready === true.
      setEnrollmentStep("studyMode");
    }
  };

  /**
   * Online + “Study at a Centre”: with-support learning mode (unless skipped), then cohort list.
   * @param {{ skipLearningMode?: boolean }} [options] — set skipLearningMode when setLearningMode was already called (legacy handleSupportAnswer(true)).
   */
  const beginOnlineCentreBatchEnrollment = async (options = {}) => {
    const { skipLearningMode = false } = options;
    const centreIdVal = enrollingCentreId || selectedCentre?.id;
    const payload = { userId: id, course_id: enrollingCourseId, ...(centreIdVal && { centre_id: centreIdVal }) };
    try {
      setEnrollSubmitting(true);
      setError(null);
      if (!skipLearningMode) {
        await setLearningMode(payload, false, token);
      }
      setNeedsSupport(true);
      setEnrollSubmitting(false);
      setBatchesLoading(true);
      setEnrollmentStep("batch");
      const batches = await fetchBatchesForCourse(enrollingCourseId);
      const hasAvailable = batches.some((b) =>
        (b.sessions || []).some((s) => Number(s.remaining) > 0),
      );
      if (!hasAvailable) {
        await fetchAlternatives(enrollingCourseId, centreIdVal);
        setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
        setEnrollmentStep("courseFull");
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      setError(apiErrors ? Object.values(apiErrors).flat().join(". ") : (apiMessage || "Failed to continue. Please try again."));
    } finally {
      setEnrollSubmitting(false);
      setBatchesLoading(false);
    }
  };

  const chooseStudyAtCentre = () => {
    if (selectedCentre?.is_ready !== true) {
      setError(
        "Centre-based study with support is not available at this centre yet. Try another centre.",
      );
      return;
    }
    setError(null);
    setStudyModeChoice("centre");
    void beginOnlineCentreBatchEnrollment();
  };

  const chooseStudyFromHome = async () => {
    setError(null);
    setStudyModeChoice("home");
    setEnrollmentStep("selfPacedCohort");
    setBatchesLoading(true);
    try {
      await fetchBatchesForCourse(enrollingCourseId);
    } catch {
      setAvailableBatches([]);
    } finally {
      setBatchesLoading(false);
    }
  };

  const pickDefaultMasterSessionForBatch = (batch) => {
    const sessions = batch?.sessions || [];
    const withRoom = sessions.find((s) => Number(s.remaining) > 0);
    return withRoom || sessions[0] || null;
  };

  const handleSelfPacedCohortSelect = (batch) => {
    const session = pickDefaultMasterSessionForBatch(batch);
    if (!session) {
      setError("No cohort timetable reference is available. Please contact support.");
      return;
    }
    setSelectedBatch(batch);
    setSelectedSession(session);
    setEnrollmentStep("selfPacedConfirm");
  };

  // Support answer: both Yes and No hit the endpoint, then branch
  const handleSupportAnswer = async (needs) => {
    setNeedsSupport(needs);
    const centreIdVal = enrollingCentreId || selectedCentre?.id;
    const payload = { userId: id, course_id: enrollingCourseId, ...(centreIdVal && { centre_id: centreIdVal }) };

    try {
      setEnrollSubmitting(true);
      setError(null);
      const modeRes = await setLearningMode(payload, !needs, token);

      if (!needs) {
        // No support → enrolled as self-paced, done
        setEnrollmentSuccessRedirectUrl(
          typeof modeRes?.redirect_url === "string" && modeRes.redirect_url ? modeRes.redirect_url : null,
        );
        setEnrollSuccess(true);
        updateQueryParams({ step: null, region: null, district: null, centre: null });
      } else {
        await beginOnlineCentreBatchEnrollment({ skipLearningMode: true });
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      setError(apiErrors ? Object.values(apiErrors).flat().join(". ") : (apiMessage || "Failed to enroll. Please try again."));
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const handleBatchSelect = (batch) => {
    // Check if batch has any sessions with remaining slots
    const hasAvailableSession = batch.sessions?.some((s) => Number(s.remaining) > 0);
    if (!hasAvailableSession) return;
    setSelectedBatch(batch);
    setEnrollmentStep("session");
  };

  const handleSessionSelect = (session) => {
    if (session.remaining === 0) return;
    setSelectedSession(session);
    setEnrollmentStep("confirm");
  };

  // Confirm enrollment via /api/bookings
  const handleEnrollSubmit = async () => {
    try {
      setEnrollSubmitting(true);
      setError(null);
      const result = await createBooking(
        {
          programme_batch_id: selectedBatch.id,
          course_id: enrollingCourseId,
          session_id: selectedSession.session_id,
        },
        token,
        { selfPace: !inPersonEnrollmentFlow && studyModeChoice === "home" },
      );
      if (result.conflict) {
        // 409 — batch filled up, re-fetch batches
        const batches = await fetchBatchesForCourse(enrollingCourseId);
        const hasAvailable = batches.some((b) =>
          (b.sessions || []).some((s) => Number(s.remaining) > 0),
        );
        if (hasAvailable) {
          setSelectedBatch(null);
          setSelectedSession(null);
          setSelectedBatchMonth(null);
          setEnrollmentStep("batchFull");
        } else {
          await fetchAlternatives(enrollingCourseId, enrollingCentreId || selectedCentre?.id);
          setCourseFullIssue(deriveAvailabilityIssueFromBatches(batches));
          setEnrollmentStep("courseFull");
        }
      } else if (inPersonEnrollmentFlow) {
        updateQueryParams({ step: null, region: null, district: null, centre: null });
        window.setTimeout(
          () => redirectToStudentDashboard(result.redirect_url),
          400,
        );
      } else {
        setEnrollmentSuccessRedirectUrl(
          typeof result?.redirect_url === "string" && result.redirect_url ? result.redirect_url : null,
        );
        setEnrollSuccess(true);
        updateQueryParams({ step: null, region: null, district: null, centre: null });
      }
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      const apiMessage = err.response?.data?.message;
      setError(apiErrors ? Object.values(apiErrors).flat().join(". ") : (apiMessage || "Failed to enroll. Please try again."));
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const handleJoinWaitlist = async () => {
    try {
      setEnrollSubmitting(true);
      await joinWaitlist(id, enrollingCourseId, token);
      setWaitlistJoined(true);
    } catch (err) {
      const apiMessage = err.response?.data?.message;
      setError(apiMessage || "Failed to join waitlist. Please try again.");
    } finally {
      setEnrollSubmitting(false);
    }
  };

  const handleSuccessfulEnrollmentClose = () => {
    redirectToStudentDashboard(enrollmentSuccessRedirectUrl || undefined);
    closeEnrollmentModal();
  };

  // Waitlist close → send the user back to the Laravel student dashboard
  // (iframe-aware), then reset modal state for the non-iframe case.
  const handleWaitlistClose = () => {
    redirectToStudentDashboard();
    closeEnrollmentModal();
  };

  const closeEnrollmentModal = () => {
    setEnrollmentStep(null);
    setEnrollingCourseId(null);
    setEnrollingCentreId(null);
    setSelectedBatch(null);
    setSelectedSession(null);
    setNeedsSupport(null);
    setWaitlistJoined(false);
    setEnrollSuccess(false);
    setAvailableBatches([]);
    setSiblingCentres([]);
    setSiblingCourses({ matches: [], available_courses: [] });
    setSelectedBatchMonth(null);
    setCourseFullTab("centres");
    setCourseFullIssue(null);
    setInPersonEnrollmentFlow(false);
    setInPersonMeta(null);
    setEnrollingCourseRecord(null);
    setEnrollmentSuccessRedirectUrl(null);
    setStudyModeChoice(null);
  };

  const goToStep = (targetStep) => {
    // Allow going forward to step 4 if quiz progress exists
    const canGoForward =
      targetStep === 4 && selectedCentre && questions.length > 0;
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
            {verifying
              ? "Verifying your account..."
              : "Checking your courses..."}
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

  const renderResultsEnrollmentModal = () => (
    <>
              {/* Enrollment Sub-Flow Modal: studyMode / batch → session → confirm / courseFull */}
              <AnimatePresence>
                {(enrollmentStep || enrollSuccess) && (
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
                      transition={{
                        type: "spring",
                        damping: 28,
                        stiffness: 300,
                      }}
                      className="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-xl px-5 py-6 sm:p-8 relative max-h-[85vh] sm:max-h-[90vh] overflow-y-auto"
                    >
                      {/* ── Success State ── */}
                      {enrollSuccess && (
                        <div className="text-center">
                          <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                          </div>
                          <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                            You&apos;re enrolled!
                          </h2>
                          <p className="text-gray-500 text-sm sm:text-base mb-2">
                            You have been successfully enrolled in{" "}
                            <span className="font-semibold text-gray-700">
                              {enrolledCourseName}
                            </span>
                            .
                          </p>
                          {selectedBatch && selectedSession && (
                            <p className="text-gray-400 text-xs sm:text-sm mb-6">
                              {studyModeChoice === "home" ? (
                                <>
                                  {selectedBatch.batch || `Batch ${selectedBatch.id}`} · Self-paced (study from home)
                                </>
                              ) : (
                                <>
                                  {selectedBatch.batch || `Batch ${selectedBatch.id}`} · {selectedSession.session_name}{" "}
                                  ({selectedSession.time})
                                </>
                              )}
                            </p>
                          )}
                          <button
                            onClick={handleSuccessfulEnrollmentClose}
                            className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                          >
                            Close
                          </button>
                        </div>
                      )}

                      {/* ── Waitlist Joined State ── */}
                      {!enrollSuccess && waitlistJoined && (
                        <div className="text-center">
                          <div className="w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <FiCheckCircle className="w-7 h-7 sm:w-8 sm:h-8 text-green-600" />
                          </div>
                          <h2 className="text-lg sm:text-2xl font-bold text-gray-900 mb-2">
                            You&apos;re on the waitlist!
                          </h2>
                          <p className="text-gray-500 text-sm sm:text-base mb-6">
                            We&apos;ll notify you when a slot opens up for{" "}
                            <span className="font-semibold text-gray-700">
                              {enrolledCourseName}
                            </span>
                            .
                          </p>
                          <button
                            onClick={handleWaitlistClose}
                            className="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                          >
                            Go to my dashboard
                          </button>
                        </div>
                      )}

                      {/* ── Step 1: Batch Selection with Month Tabs ── */}
                      {!enrollSuccess && !waitlistJoined && enrollmentStep === "batch" && (() => {
                        const monthMap = {};
                        availableBatches.forEach((b) => {
                          const key = String(b.start_date ?? "").slice(0, 7);
                          if (!key) return;
                          if (!monthMap[key]) monthMap[key] = [];
                          monthMap[key].push(b);
                        });
                        const months = Object.keys(monthMap).sort();
                        const activeMonth = selectedBatchMonth || months[0];
                        const filteredBatches = monthMap[activeMonth] || [];
                        const batchTotalRemaining = (b) => (b.sessions || []).reduce((sum, s) => sum + s.remaining, 0);
                        return (
                          <>
                            <button onClick={closeEnrollmentModal} className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"><FiX className="w-4.5 h-4.5" /></button>
                            <div className="flex justify-center mb-3 sm:hidden"><div className="w-8 h-1 bg-gray-200 rounded-full" /></div>

                            <div className="flex items-center gap-1.5 mb-5">
                              {[{ label: "Batch", step: "batch" }, { label: "Session", step: "session" }, { label: "Confirm", step: "confirm" }].map(({ label, step: s }, i) => {
                                const currentIdx = 0; const isDone = i < currentIdx; const isCurrent = i === currentIdx; const canClick = isDone;
                                return (
                                  <React.Fragment key={label}>
                                    <button disabled={!canClick} onClick={() => canClick && setEnrollmentStep(s)} className={`flex items-center gap-1 ${isDone ? "text-green-500 cursor-pointer" : isCurrent ? "text-yellow-600" : "text-gray-300"} ${canClick ? "hover:opacity-80" : ""}`}>
                                      <div className={`w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ${isDone ? "bg-green-500 text-white" : isCurrent ? "bg-yellow-400 text-gray-900" : "bg-gray-100 text-gray-400"}`}>{isDone ? <FiCheckCircle className="w-3 h-3" /> : i + 1}</div>
                                      <span className="text-[11px] font-medium">{label}</span>
                                    </button>
                                    {i < 2 && <div className={`flex-1 h-px ${isDone ? "bg-green-300" : "bg-gray-200"}`} />}
                                  </React.Fragment>
                                );
                              })}
                            </div>

                            {!inPersonEnrollmentFlow && studyModeChoice === "centre" && (
                              <button
                                type="button"
                                onClick={() => {
                                  setError(null);
                                  setNeedsSupport(null);
                                  setSelectedBatch(null);
                                  setSelectedSession(null);
                                  setSelectedBatchMonth(null);
                                  setEnrollmentStep("studyMode");
                                }}
                                className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                              >
                                <FiArrowLeft className="w-3 h-3" /> Back
                              </button>
                            )}

                            <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-0.5">When would you like to start?</h2>
                            <p className="text-gray-500 text-sm sm:text-base mb-4 line-clamp-2 leading-snug">{enrolledCourseName}</p>

                            {batchesLoading ? (
                              <div className="flex items-center justify-center py-12">
                                <FiLoader className="w-5 h-5 text-yellow-500 animate-spin" />
                              </div>
                            ) : (
                              <>
                                {/* Month pills */}
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

                                {/* Batch list */}
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

                      {/* ── Step 2: Session Selection ── */}
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

                            {/* Step indicator */}
                            <div className="flex items-center gap-1.5 mb-5">
                              {[{ label: "Batch", step: "batch" }, { label: "Session", step: "session" }, { label: "Confirm", step: "confirm" }].map(({ label, step: s }, i) => {
                                const currentIdx = 1;
                                const isDone = i < currentIdx;
                                const isCurrent = i === currentIdx;
                                const canClick = isDone;
                                return (
                                  <React.Fragment key={label}>
                                    <button disabled={!canClick} onClick={() => canClick && setEnrollmentStep(s)} className={`flex items-center gap-1 ${isDone ? "text-green-500 cursor-pointer" : isCurrent ? "text-yellow-600" : "text-gray-300"} ${canClick ? "hover:opacity-80" : ""}`}>
                                      <div className={`w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ${isDone ? "bg-green-500 text-white" : isCurrent ? "bg-yellow-400 text-gray-900" : "bg-gray-100 text-gray-400"}`}>{isDone ? <FiCheckCircle className="w-3 h-3" /> : i + 1}</div>
                                      <span className="text-[11px] font-medium">{label}</span>
                                    </button>
                                    {i < 2 && <div className={`flex-1 h-px ${isDone ? "bg-green-300" : "bg-gray-200"}`} />}
                                  </React.Fragment>
                                );
                              })}
                            </div>

                            {/* Mobile drag handle */}
                            <div className="flex justify-center mb-3 sm:hidden">
                              <div className="w-8 h-1 bg-gray-200 rounded-full" />
                            </div>

                            <button
                              onClick={() => setEnrollmentStep("batch")}
                              className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                            >
                              <FiArrowLeft className="w-3 h-3" /> Back
                            </button>
                            <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">
                              Choose your session
                            </h2>

                            {/* Batch summary — compact on mobile */}
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
                                  <button
                                    key={session.session_id}
                                    onClick={() => handleSessionSelect(session)}
                                    disabled={isFull}
                                    className={`w-full text-left p-3 sm:p-4 rounded-xl border transition-all duration-200 group active:scale-[0.99] ${
                                      isFull ? "bg-gray-50/80 border-gray-100 cursor-not-allowed opacity-50"
                                        : isSelected ? "bg-gray-900 text-white border-gray-900 shadow-lg"
                                        : "bg-white border-gray-200 hover:border-yellow-400 hover:shadow-md"
                                    }`}
                                  >
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
                                        ) : (
                                          <span className={`text-[10px] tabular-nums ${isSelected ? "text-yellow-400" : session.remaining <= 5 ? "text-orange-600 font-medium" : "text-gray-400"}`}>{session.remaining} left</span>
                                        )}
                                        {!isFull && !isSelected && <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-yellow-500 transition-all group-hover:translate-x-0.5" />}
                                      </div>
                                    </div>
                                  </button>
                                );
                              })}
                            </div>
                          </>
                        )}

                      {/* ── Online: study at centre vs study from home ── */}
                      {!enrollSuccess &&
                        !waitlistJoined &&
                        enrollmentStep === "studyMode" && (
                          <>
                            <button
                              type="button"
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
                              <p className="text-gray-400 text-[11px] sm:text-sm line-clamp-2">
                                Enrolling in{" "}
                                <span className="text-gray-600">{enrolledCourseName}</span>
                              </p>
                              {selectedCentre?.is_ready !== true && (
                                <p className="text-gray-500 text-[11px] sm:text-xs mt-2 leading-relaxed px-1">
                                  Centre-based study with on-site support isn&apos;t available at this location yet.
                                  You can still enrol and learn from home (self-paced).
                                </p>
                              )}
                            </div>
                            {error && (
                              <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                                <p className="text-red-700 text-sm">{error}</p>
                              </div>
                            )}
                            <div className="space-y-3">
                              {selectedCentre?.is_ready === true && (
                                <button
                                  type="button"
                                  onClick={chooseStudyAtCentre}
                                  className="w-full text-left p-4 rounded-2xl border-2 border-gray-200 bg-white hover:border-yellow-400 hover:shadow-md transition-all flex items-stretch gap-3 active:scale-[0.99]"
                                >
                                  <div className="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <FiMapPin className="w-5 h-5 text-yellow-800" />
                                  </div>
                                  <div className="flex-1 min-w-0">
                                    <div className="font-semibold text-gray-900 text-sm sm:text-base">Study at a Centre</div>
                                    <p className="text-[11px] sm:text-xs text-gray-600 mt-1 leading-snug">
                                      Laptop and internet access provided at our support centre, with staff on hand.
                                    </p>
                                  </div>
                                  <FiChevronRight className="w-5 h-5 text-gray-400 self-center flex-shrink-0" />
                                </button>
                              )}
                              <button
                                type="button"
                                onClick={() => void chooseStudyFromHome()}
                                className="w-full text-left p-4 rounded-2xl border-2 border-gray-200 bg-white hover:border-sky-400 hover:shadow-md transition-all flex items-stretch gap-3 active:scale-[0.99]"
                              >
                                <div className="w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center flex-shrink-0">
                                  <FiMonitor className="w-5 h-5 text-sky-800" />
                                </div>
                                <div className="flex-1 min-w-0">
                                  <div className="font-semibold text-gray-900 text-sm sm:text-base">Study from Home</div>
                                  <p className="text-[11px] sm:text-xs text-gray-600 mt-1 leading-snug">
                                    Complete your course entirely online using your own device, at your own pace.
                                  </p>
                                </div>
                                <FiChevronRight className="w-5 h-5 text-gray-400 self-center flex-shrink-0" />
                              </button>
                            </div>
                            <button
                              type="button"
                              onClick={closeEnrollmentModal}
                              className="w-full mt-5 py-2.5 text-sm text-gray-400 hover:text-gray-600 font-medium transition-colors"
                            >
                              Cancel
                            </button>
                          </>
                        )}

                      {/* ── Study from home: cohort only (no seat counts) ── */}
                      {!enrollSuccess &&
                        !waitlistJoined &&
                        enrollmentStep === "selfPacedCohort" &&
                        (() => {
                          const monthMap = {};
                          availableBatches.forEach((b) => {
                            const key = String(b.start_date ?? "").slice(0, 7);
                            if (!key) return;
                            if (!monthMap[key]) monthMap[key] = [];
                            monthMap[key].push(b);
                          });
                          const months = Object.keys(monthMap).sort();
                          const activeMonth = selectedBatchMonth || months[0];
                          const filteredBatches = monthMap[activeMonth] || [];
                          return (
                            <>
                              <button
                                type="button"
                                onClick={closeEnrollmentModal}
                                className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                              >
                                <FiX className="w-4.5 h-4.5" />
                              </button>
                              <div className="flex justify-center mb-3 sm:hidden">
                                <div className="w-8 h-1 bg-gray-200 rounded-full" />
                              </div>
                              <button
                                type="button"
                                onClick={() => {
                                  setError(null);
                                  setEnrollmentStep("studyMode");
                                }}
                                className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                              >
                                <FiArrowLeft className="w-3 h-3" /> Back
                              </button>
                              <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-2">Choose your cohort</h2>
                              <p className="text-gray-600 text-xs sm:text-sm mb-3 leading-relaxed">
                                You are enrolling for <span className="font-semibold text-gray-800">{enrolledCourseName}</span>{" "}
                                as <span className="font-semibold">self-paced (study from home)</span>. Please pick the cohort
                                (batch) you want your records grouped under — this helps us locate your file quickly.{" "}
                                <span className="text-gray-500">Seat counts do not apply to self-paced home study.</span>
                              </p>
                              {error && (
                                <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                                  <p className="text-red-700 text-sm">{error}</p>
                                </div>
                              )}
                              {batchesLoading ? (
                                <div className="flex items-center justify-center py-12">
                                  <FiLoader className="w-5 h-5 text-yellow-500 animate-spin" />
                                </div>
                              ) : (
                                <>
                                  {months.length > 1 && (
                                    <div className="flex gap-1.5 overflow-x-auto scrollbar-hide pb-1 mb-4 -mx-1 px-1">
                                      {months.map((m) => {
                                        const isActive = m === activeMonth;
                                        const label = new Date(m + "-01").toLocaleDateString("en-GB", { month: "short" });
                                        return (
                                          <button
                                            key={m}
                                            type="button"
                                            onClick={() => setSelectedBatchMonth(m)}
                                            className={`flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all flex-shrink-0 ${isActive ? "bg-gray-900 text-white" : "bg-gray-100 text-gray-700 hover:bg-gray-200"}`}
                                          >
                                            {label}
                                          </button>
                                        );
                                      })}
                                    </div>
                                  )}
                                  <div className="space-y-2">
                                    {filteredBatches.map((batch) => {
                                      const startStr = new Date(batch.start_date).toLocaleDateString("en-GB", {
                                        day: "numeric",
                                        month: "short",
                                      });
                                      const endStr = new Date(batch.end_date).toLocaleDateString("en-GB", {
                                        day: "numeric",
                                        month: "short",
                                      });
                                      return (
                                        <button
                                          key={batch.id}
                                          type="button"
                                          onClick={() => handleSelfPacedCohortSelect(batch)}
                                          className="w-full text-left p-3 sm:p-4 rounded-xl border border-gray-200 bg-white hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.99]"
                                        >
                                          <div className="flex items-center gap-3">
                                            <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0 bg-gradient-to-br from-sky-50 to-blue-50 group-hover:from-sky-100 group-hover:to-blue-100 transition-colors">
                                              <span className="text-[10px] font-medium leading-none text-sky-800">
                                                {new Date(batch.start_date).toLocaleDateString("en-GB", { month: "short" })}
                                              </span>
                                              <span className="text-base font-bold leading-tight text-gray-900">
                                                {new Date(batch.start_date).getDate()}
                                              </span>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                              <div className="text-sm font-semibold text-gray-900 group-hover:text-sky-800 transition-colors">
                                                {batch.batch || `Cohort ${batch.id}`}
                                              </div>
                                              <div className="text-[11px] sm:text-xs text-gray-500 mt-0.5">
                                                {startStr} — {endStr}
                                              </div>
                                            </div>
                                            <FiChevronRight className="w-4 h-4 text-gray-300 group-hover:text-sky-600 flex-shrink-0 transition-all group-hover:translate-x-0.5" />
                                          </div>
                                        </button>
                                      );
                                    })}
                                    {filteredBatches.length === 0 && (
                                      <div className="text-center py-8 text-gray-400 text-sm">No cohorts in this period</div>
                                    )}
                                  </div>
                                </>
                              )}
                            </>
                          );
                        })()}

                      {/* ── Study from home: summary & confirm ── */}
                      {!enrollSuccess &&
                        !waitlistJoined &&
                        enrollmentStep === "selfPacedConfirm" && (
                          <>
                            <button
                              type="button"
                              onClick={closeEnrollmentModal}
                              className="absolute top-3 right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                            >
                              <FiX className="w-4.5 h-4.5" />
                            </button>
                            <div className="flex justify-center mb-3 sm:hidden">
                              <div className="w-8 h-1 bg-gray-200 rounded-full" />
                            </div>
                            <div className="flex items-center gap-1.5 mb-5">
                              {["Cohort", "Confirm"].map((label, i) => (
                                <React.Fragment key={label}>
                                  <div className="flex items-center gap-1">
                                    <div
                                      className={`w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ${i === 0 ? "bg-green-500 text-white" : "bg-yellow-400 text-gray-900"}`}
                                    >
                                      {i === 0 ? <FiCheckCircle className="w-3 h-3" /> : "2"}
                                    </div>
                                    <span className={`text-[11px] font-medium ${i === 0 ? "text-green-600" : "text-yellow-600"}`}>{label}</span>
                                  </div>
                                  {i < 1 && <div className="flex-1 h-px bg-green-300" />}
                                </React.Fragment>
                              ))}
                            </div>
                            <button
                              type="button"
                              onClick={() => setEnrollmentStep("selfPacedCohort")}
                              className="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-gray-600 transition-colors mb-3"
                            >
                              <FiArrowLeft className="w-3 h-3" /> Back
                            </button>
                            <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">Review and confirm</h2>
                            {(() => {
                              const regionName =
                                (inPersonMeta?.region_name && String(inPersonMeta.region_name).trim()) ||
                                selectedRegion?.title ||
                                "";
                              const districtName =
                                (inPersonMeta?.district_name && String(inPersonMeta.district_name).trim()) ||
                                selectedDistrict?.title ||
                                "";
                              const regionDistrict = [regionName, districtName].filter(Boolean).join(" → ");
                              const centreName =
                                (inPersonMeta?.centre_title && String(inPersonMeta.centre_title).trim()) ||
                                selectedCentre?.title ||
                                "";
                              const awardTitle =
                                (inPersonMeta?.certificate_title && String(inPersonMeta.certificate_title).trim()) ||
                                enrollingCourseRecord?.course_certification?.[0]?.title ||
                                enrollingCourseRecord?.courseCertification?.[0]?.title ||
                                "";
                              return (
                                <>
                                  {/* <div className="mb-4 rounded-xl border border-sky-100 bg-sky-50/80 px-3 py-3 text-[11px] sm:text-xs text-sky-950 leading-relaxed">
                                    You chose <strong>self-paced study from home</strong>. Your official completion award can
                                    still be verified and collected at the centre listed below — the same region and centre
                                    where classmates may attend in person if they need equipment or connectivity support.
                                  </div> */}
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
                                        <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">
                                          {regionDistrict || "—"}
                                        </dd>
                                      </div>
                                      <div>
                                        <dt className="text-xs sm:text-sm font-medium text-gray-500">Award / records centre</dt>
                                        <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">
                                          {centreName || "—"}
                                        </dd>
                                      </div>
                                      <div>
                                        <dt className="text-xs sm:text-sm font-medium text-gray-500">Award on completion</dt>
                                        <dd className="mt-1 font-semibold text-gray-900 break-words leading-snug">
                                          {awardTitle || "—"}
                                        </dd>
                                      </div>
                                      <div className="pt-1 border-t border-gray-200/70">
                                        <dt className="text-xs sm:text-sm font-medium text-gray-500">Administrative cohort</dt>
                                        <dd className="mt-1 font-semibold text-gray-900 break-words">
                                          {selectedBatch?.batch || `Batch ${selectedBatch?.id}`}
                                          {selectedBatch?.start_date && (
                                            <>
                                              {" "}
                                              ·{" "}
                                              {new Date(selectedBatch.start_date).toLocaleDateString("en-GB", {
                                                month: "short",
                                                year: "numeric",
                                              })}
                                            </>
                                          )}
                                        </dd>
                                      </div>
                                      <div>
                                        <dt className="text-xs sm:text-sm font-medium text-gray-500">Learning mode</dt>
                                        <dd className="mt-1 font-semibold text-gray-900 break-words">
                                          Study from home (self-paced)
                                        </dd>
                                      </div>
                                    </dl>
                                  </div>
                                </>
                              );
                            })()}
                            {error && (
                              <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                                <p className="text-red-700 text-sm">{error}</p>
                              </div>
                            )}
                            <div className="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2.5">
                              <button
                                type="button"
                                onClick={closeEnrollmentModal}
                                className="flex-1 py-3.5 sm:py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold text-sm sm:text-base rounded-xl transition-colors"
                              >
                                Cancel
                              </button>
                              <button
                                type="button"
                                onClick={handleEnrollSubmit}
                                disabled={enrollSubmitting}
                                className={`flex-1 py-3.5 sm:py-3 font-semibold text-sm sm:text-base rounded-xl transition-all flex items-center justify-center gap-2 active:scale-[0.97] ${!enrollSubmitting ? "bg-yellow-400 hover:bg-yellow-500 text-gray-900 shadow-sm" : "bg-gray-100 text-gray-400 cursor-not-allowed"}`}
                              >
                                {enrollSubmitting ? (
                                  <>
                                    <FiLoader className="w-4 h-4 animate-spin" />
                                    Confirming…
                                  </>
                                ) : (
                                  "Confirm enrolment"
                                )}
                              </button>
                            </div>
                          </>
                        )}

                      {/* ── Confirm Enrollment (after batch + session) ── */}
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
                            {/* Mobile drag handle */}
                            <div className="flex justify-center mb-3 sm:hidden">
                              <div className="w-8 h-1 bg-gray-200 rounded-full" />
                            </div>

                            {/* Step indicator */}
                            <div className="flex items-center gap-1.5 mb-5">
                              {[{ label: "Batch", step: "batch" }, { label: "Session", step: "session" }, { label: "Confirm", step: "confirm" }].map(({ label, step: s }, i) => {
                                const currentIdx = 2;
                                const isDone = i < currentIdx;
                                const isCurrent = i === currentIdx;
                                const canClick = isDone;
                                return (
                                  <React.Fragment key={label}>
                                    <button disabled={!canClick} onClick={() => canClick && setEnrollmentStep(s)} className={`flex items-center gap-1 ${isDone ? "text-green-500 cursor-pointer" : isCurrent ? "text-yellow-600" : "text-gray-300"} ${canClick ? "hover:opacity-80" : ""}`}>
                                      <div className={`w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center ${isDone ? "bg-green-500 text-white" : isCurrent ? "bg-yellow-400 text-gray-900" : "bg-gray-100 text-gray-400"}`}>{isDone ? <FiCheckCircle className="w-3 h-3" /> : i + 1}</div>
                                      <span className="text-[11px] font-medium">{label}</span>
                                    </button>
                                    {i < 2 && <div className={`flex-1 h-px ${isDone ? "bg-green-300" : "bg-gray-200"}`} />}
                                  </React.Fragment>
                                );
                              })}
                            </div>

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
                              const regionName =
                                (inPersonMeta?.region_name && String(inPersonMeta.region_name).trim()) ||
                                selectedRegion?.title ||
                                "";
                              const districtName =
                                (inPersonMeta?.district_name && String(inPersonMeta.district_name).trim()) ||
                                selectedDistrict?.title ||
                                "";
                              const regionDistrict = [regionName, districtName].filter(Boolean).join(" → ");
                              const centreName =
                                (inPersonMeta?.centre_title && String(inPersonMeta.centre_title).trim()) ||
                                selectedCentre?.title ||
                                "";
                              const awardTitle =
                                (inPersonMeta?.certificate_title && String(inPersonMeta.certificate_title).trim()) ||
                                enrollingCourseRecord?.course_certification?.[0]?.title ||
                                enrollingCourseRecord?.courseCertification?.[0]?.title ||
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

                      {/* ── Centre Full — Tabbed recommendations ── */}
                      {!enrollSuccess &&
                        !waitlistJoined &&
                        enrollmentStep === "courseFull" && (() => {
                          const fullCopy = courseFullModalCopy(courseFullIssue);
                          return (
                          <>
                            <div className="flex justify-center mb-3 sm:hidden"><div className="w-8 h-1 bg-gray-200 rounded-full" /></div>
                            <button onClick={closeEnrollmentModal} className="absolute top-3 right-3 z-10 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"><FiX className="w-4.5 h-4.5" /></button>

                            <div className="text-center mb-4 px-1">
                              <h2 className="text-lg sm:text-xl font-bold text-gray-900 mb-1.5">{fullCopy.title}</h2>
                              <p className="text-sm sm:text-base font-semibold text-gray-700 mb-1">{enrolledCourseName}</p>
                              <p className="text-xs sm:text-sm text-gray-600 mb-2 max-w-md mx-auto leading-relaxed px-1">{fullCopy.detail}</p>
                              <div className="flex items-center justify-center gap-1.5 text-xs sm:text-sm text-gray-700">
                                <FiMapPin className="w-3 h-3 flex-shrink-0" />
                                <span>{selectedCentre?.title}</span>
                              </div>
                            </div>

                            {/* Tabs */}
                            <div className="flex bg-gray-200/70 rounded-xl p-1 mb-5">
                              <button onClick={() => setCourseFullTab("centres")}
                                className={`flex-1 py-3 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all text-center ${courseFullTab === "centres" ? "bg-white text-gray-900 shadow-md ring-1 ring-gray-200/50" : "text-gray-500 hover:text-gray-800"}`}>
                                Find another centre
                              </button>
                              <button onClick={() => setCourseFullTab("courses")}
                                className={`flex-1 py-3 px-3 rounded-lg text-xs sm:text-sm font-semibold transition-all text-center ${courseFullTab === "courses" ? "bg-white text-gray-900 shadow-md ring-1 ring-gray-200/50" : "text-gray-500 hover:text-gray-800"}`}>
                                Explore other courses
                              </button>
                            </div>

                            {/* Tab content */}
                            <div className="mb-5 min-h-[140px]">
                              <AnimatePresence mode="wait" initial={false}>
                              {courseFullTab === "centres" && (
                                <motion.div key="centres-tab" initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 10 }} transition={{ duration: 0.2 }}>
                                  <h4 className="text-[10px] sm:text-[11px] font-bold text-gray-700 uppercase tracking-widest mb-3">Available nearby</h4>
                                  <div className="space-y-2">
                                    {siblingCentres.map((alt, idx) => (
                                      <motion.button key={alt.centre_id} initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.2, delay: idx * 0.05 }}
                                        onClick={() => { setSelectedCentre({ id: alt.centre_id, title: alt.centre_name }); setEnrollingCentreId(alt.centre_id); setEnrollingCourseId(alt.course_id || enrollingCourseId); setEnrollingCourseRecord((prev) => ({ ...(prev || {}), course_id: alt.course_id || enrollingCourseId, title: prev?.title || enrolledCourseName })); setSelectedBatch(null); setSelectedSession(null); setSelectedBatchMonth(null); setCourseFullTab("centres"); fetchBatchesForCourse(alt.course_id || enrollingCourseId).then(() => setEnrollmentStep("batch")); }}
                                        className="w-full text-left p-3 sm:p-4 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98]">
                                        <div className="flex items-center justify-between gap-3">
                                          <div className="min-w-0 flex-1">
                                            <div className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors truncate">{alt.centre_name}</div>
                                            {(alt.district_name || alt.branch_name) && (
                                              <div className="flex items-center gap-1 text-[11px] text-gray-500 mt-0.5 truncate">
                                                <FiMapPin className="w-3 h-3 text-gray-400 flex-shrink-0" />
                                                <span className="truncate">
                                                  {[alt.district_name, alt.branch_name].filter(Boolean).join(" · ")}
                                                </span>
                                              </div>
                                            )}
                                            <div className="text-xs text-green-600 font-medium mt-1">{alt.available} slots available</div>
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
                                      <button key={alt.course_id || alt.id} onClick={() => { setEnrolledCourseName(alt.title); setEnrollingCourseId(alt.course_id || alt.id); setEnrollingCourseRecord(alt); setEnrollingCentreId(alt.centre_id || enrollingCentreId); setSelectedBatch(null); setSelectedSession(null); setSelectedBatchMonth(null); setCourseFullTab("centres"); fetchBatchesForCourse(alt.course_id || alt.id).then(() => setEnrollmentStep("batch")); }}
                                        className="text-left rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] overflow-hidden">
                                        <div className="relative h-20 sm:h-24 bg-gray-100">
                                          {alt.image && !imageErrors[`alt-${alt.course_id || alt.id}`] ? (
                                            <Image src={alt.image} alt={alt.title} fill className="object-cover" onError={() => setImageErrors((prev) => ({ ...prev, [`alt-${alt.course_id || alt.id}`]: true }))} />
                                          ) : (
                                            <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center"><Image src="/images/one-million-coders-logo.png" alt="One Million Coders" width={60} height={20} className="opacity-15" /></div>
                                          )}
                                          <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
                                          {alt.match_percentage && (
                                            <div className="absolute bottom-1.5 right-1.5">
                                              <span className="flex items-center gap-0.5 px-1.5 py-0.5 bg-white/90 text-[9px] sm:text-[10px] font-medium rounded-full backdrop-blur-sm text-yellow-700">
                                                <FiStar className="w-2.5 h-2.5" />
                                                {alt.match_percentage}
                                              </span>
                                            </div>
                                          )}
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

                            {/* Bottom section */}
                            <div className="pt-4 border-t border-gray-100">
                              <h4 className="text-[10px] sm:text-[11px] font-bold text-gray-700 uppercase tracking-widest text-center mb-3">Or</h4>
                              <div className={`grid gap-2 ${inPersonEnrollmentFlow ? "grid-cols-1" : "grid-cols-2"}`}>
                                {!inPersonEnrollmentFlow && (
                                  <button onClick={() => handleSupportAnswer(false)} disabled={enrollSubmitting}
                                    className="p-3 sm:p-4 rounded-xl bg-white border border-gray-200 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] text-center">
                                    <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 flex items-center justify-center mx-auto mb-2 group-hover:from-green-100 group-hover:to-emerald-100 transition-colors">
                                      {enrollSubmitting ? <FiLoader className="w-4 h-4 text-green-600 animate-spin" /> : <FiCheckCircle className="w-4 h-4 text-green-600" />}
                                    </div>
                                    <div className="text-xs sm:text-sm font-semibold text-gray-900 mb-0.5">Enroll without support</div>
                                    <div className="text-[10px] sm:text-[11px] text-gray-400 leading-tight">Skip support, enroll now</div>
                                  </button>
                                )}
                                <button onClick={handleJoinWaitlist}
                                  className="p-3 sm:p-4 rounded-xl bg-white border border-dashed border-gray-300 hover:border-yellow-400 hover:shadow-md transition-all duration-200 group active:scale-[0.98] text-center">
                                  <div className="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center mx-auto mb-2 group-hover:bg-yellow-100 transition-colors">
                                    <FiClock className="w-4 h-4 text-yellow-600" />
                                  </div>
                                  <div className="text-xs sm:text-sm font-semibold text-gray-900 mb-0.5">Join the waitlist</div>
                                  <div className="text-[10px] sm:text-[11px] text-gray-400 leading-tight">Get notified when available</div>
                                </button>
                              </div>
                            </div>
                          </>
                          );
                        })()}

                      {/* ── Batch Full (after confirm) — Show alternatives ── */}
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

                            {/* Pick a different batch */}
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

                            {/* Other centres */}
                            <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-4 mb-2.5">
                              Or try another centre
                            </h3>
                            <div className="space-y-2 mb-4">
                              {siblingCentres.map((alt) => (
                                <button
                                  key={alt.centre_id}
                                  onClick={() => {
                                    setSelectedCentre({ id: alt.centre_id, title: alt.centre_name });
                                    setEnrollingCentreId(alt.centre_id);
                                    setEnrollingCourseId(alt.course_id || enrollingCourseId);
                                    setEnrollingCourseRecord((prev) => ({ ...(prev || {}), course_id: alt.course_id || enrollingCourseId, title: prev?.title || enrolledCourseName }));
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
                                      {(alt.district_name || alt.branch_name) && (
                                        <div className="text-[11px] text-gray-500 mt-0.5 truncate">
                                          {[alt.district_name, alt.branch_name].filter(Boolean).join(" · ")}
                                        </div>
                                      )}
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

                            {/* Waitlist */}
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
    </>
  );

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
              {previousRecommendations.title}
            </h2>
            <p className="text-gray-500 text-xs sm:text-base max-w-lg mx-auto">
              {previousRecommendations.description ||
                "Based on your previous preferences, here are recommended courses that best align with your goals"}
            </p>
          </div>

          {/* Course cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
            {previousRecommendations.matches.map((course, index) => (
              <div
                key={course.id}
                className="rounded-lg bg-white border border-gray-200 overflow-hidden"
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
                    {course.rank || `#${index + 1}`}
                  </div>
                  <div className="absolute top-1.5 right-1.5 flex items-center gap-1">
                    {course.match_percentage && (
                      <span
                        className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium backdrop-blur-sm ${
                          parseInt(course.match_percentage.split("%")[0]) >= 70
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
                  {course.slot_left != null && (
                    <div className="absolute bottom-1.5 left-1.5">
                      <span className={`px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold rounded-full backdrop-blur-sm ${course.slot_left > 0 ? "bg-green-500/90 text-white" : "bg-red-500/90 text-white"}`}>
                        {course.slot_left > 0 ? `${course.slot_left} slots` : "Full"}
                      </span>
                    </div>
                  )}
                </div>
                <div className="p-2.5 sm:p-3">
                  <h3 className="text-xs sm:text-sm font-semibold text-gray-900 mb-1 line-clamp-2 leading-tight">
                    {course.title}
                  </h3>
                  {course.sub_title && (
                    <div className="flex items-center justify-between gap-1 mb-2 transition-colors">
                      <div className="text-sm text-gray-600 line-clamp-1">
                        {course.sub_title}
                      </div>
                      {userStatus && (
                        <a
                          href={`/programmes/${course.id}`}
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
                    onClick={() => handleEnrollClick(course)}
                    disabled={enrollSubmitting}
                    className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-xs rounded-lg transition-colors disabled:opacity-50"
                  >
                    Enroll Now
                    <FiChevronRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>

          {/* Retake Quiz button */}
          <div className="mt-8 sm:mt-10 flex justify-center">
            <button
              onClick={handleStartQuizFlow}
              className="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold text-sm rounded-xl transition-colors"
            >
              <FiTarget className="w-4 h-4" />
              Get New Recommendations
            </button>
          </div>
        </motion.div>

        {/* Enrollment modal (shared with main course flow) */}
        {renderResultsEnrollmentModal()}
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
                    disabled={
                      num >= step &&
                      !(num === 4 && selectedCentre && questions.length > 0)
                    }
                    className="flex items-center gap-1 sm:gap-2 group flex-shrink-0"
                  >
                    <div
                      className={`w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold transition-all duration-300 ${
                        step > num ||
                        (num === 4 &&
                          step === 3 &&
                          selectedCentre &&
                          questions.length > 0)
                          ? "bg-green-500 text-white cursor-pointer group-hover:bg-green-600"
                          : step === num
                            ? "bg-yellow-400 text-gray-900 ring-2 sm:ring-4 ring-yellow-100"
                            : "bg-gray-200 text-gray-400"
                      }`}
                    >
                      {step > num ||
                      (num === 4 &&
                        step === 3 &&
                        selectedCentre &&
                        questions.length > 0) ? (
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
                  <span className="truncate max-w-[80px] sm:max-w-none">
                    {selectedRegion.title}
                  </span>
                </button>
              )}
              {selectedDistrict && (
                <>
                  <FiChevronRight className="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-300 flex-shrink-0" />
                  <button
                    onClick={() => goToStep(2)}
                    className="flex items-center gap-1 sm:gap-1.5 hover:text-yellow-600 transition-colors py-0.5 min-w-0"
                  >
                    <span className="truncate max-w-[80px] sm:max-w-none">
                      {selectedDistrict.title}
                    </span>
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
                    <span className="truncate max-w-[80px] sm:max-w-none">
                      {selectedCentre.title}
                    </span>
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
                        region.title
                          .toLowerCase()
                          .includes(searchQuery.toLowerCase()),
                      )
                      .map((region) => (
                        <button
                          key={region.id}
                          onClick={() => handleRegionSelect(region)}
                          className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.97] group"
                        >
                          <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                            {region.title}
                          </h3>
                        </button>
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
                        district.title
                          .toLowerCase()
                          .includes(searchQuery.toLowerCase()),
                      )
                      .map((district) => (
                        <button
                          key={district.id}
                          onClick={() => handleDistrictSelect(district)}
                          className="p-2.5 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.97] group"
                        >
                          <h3 className="text-xs sm:text-base font-semibold text-gray-900 group-hover:text-yellow-700 leading-tight">
                            {district.title}
                          </h3>
                        </button>
                      ))}
                    {availableDistricts.districts.filter((district) =>
                      district.title
                        .toLowerCase()
                        .includes(searchQuery.toLowerCase()),
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
                        c.title
                          .toLowerCase()
                          .includes(searchQuery.toLowerCase()),
                      )
                      .map((centre, index) => {
                        const accessibilityFeatures = [
                          centre.wheelchair_accessible &&
                            "Wheelchair accessible",
                          centre.has_access_ramp && "Access ramp",
                          centre.has_accessible_toilet && "Accessible toilet",
                          centre.has_elevator && "Elevator",
                          centre.supports_hearing_impaired && "Hearing support",
                          centre.supports_visually_impaired && "Visual support",
                        ].filter(Boolean);

                        const hasExtras =
                          centre.is_pwd_friendly ||
                          accessibilityFeatures.length > 0;

                        return (
                          <button
                            key={centre.id}
                            onClick={() => handleCentreSelect(centre)}
                            className="w-full p-3 sm:p-5 rounded-xl bg-white border border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:shadow-md active:scale-[0.99] group"
                          >
                            <div
                              className={`flex justify-between gap-2 ${hasExtras ? "items-start" : "items-center"}`}
                            >
                              <div
                                className={`flex gap-2.5 sm:gap-3 min-w-0 flex-1 ${hasExtras ? "items-start" : "items-center"}`}
                              >
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
                                        {accessibilityFeatures.map(
                                          (feature) => (
                                            <span
                                              key={feature}
                                              className="text-[9px] sm:text-[11px] px-1.5 sm:px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full"
                                            >
                                              {feature}
                                            </span>
                                          ),
                                        )}
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
                          </button>
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
                          c.title
                            .toLowerCase()
                            .includes(searchQuery.toLowerCase()),
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
                  Answer a few questions so we can recommend the right courses
                  for you
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
                          ((currentQuestion + 1) / questions.length) * 100,
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
                              <button
                                key={option.id}
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
                              </button>
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
                        className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 ${
                          (answers[activeQuestion.id] || []).length > 0
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
              {renderResultsEnrollmentModal()}
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
                    <div
                      key={course.id}
                      className="rounded-lg bg-white border border-gray-200 overflow-hidden"
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
                              className={`inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium backdrop-blur-sm ${
                                course.match_percentage.split("%")[0] >= 70
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
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1 mb-2">
                              {course.mode_of_delivery === "Online" ? (
                                <FiMonitor className="w-2.5 h-2.5 text-blue-600" />
                              ) : course.mode_of_delivery === "In Person" ? (
                                <FiUsers className="w-2.5 h-2.5 text-green-600" />
                              ) : (
                                <FiGlobe className="w-2.5 h-2.5 text-purple-600" />
                              )}
                              <span
                                className={`text-[10px] sm:text-[11px] font-medium ${
                                  course.mode_of_delivery === "Online"
                                    ? "text-blue-700"
                                    : course.mode_of_delivery === "In Person"
                                      ? "text-green-700"
                                      : "text-purple-700"
                                }`}
                              >
                                {course.mode_of_delivery}
                              </span>
                            </div>
                            {userStatus && (
                              <a
                                href={`/programmes/${course.id}`}
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
                        )}
                        <button
                          onClick={() => handleEnrollClick(course)}
                          className="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-xs rounded-lg transition-colors"
                        >
                          Enroll Now
                          <FiChevronRight className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
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
                    Get New Recommendations
                  </Button>
                </div>
              )}

              {/* Actions */}
              <div className="mt-8 sm:mt-10 flex justify-center">
                <Button
                  onClick={handleViewAllCourses}
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

