"use client";

import React, { useState, useCallback, useEffect, useRef } from "react";
import Image from "next/image";
import { useRouter, useSearchParams } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import {
  FiArrowRight,
  FiCheck,
  FiX,
  FiAward,
  FiLock,
  FiHome,
  FiBarChart2,
  FiClock,
  FiLoader,
  FiAlertCircle,
  FiMaximize,
  FiAlertTriangle,
} from "react-icons/fi";
import {
  fetchAssessmentQuestions,
  submitAssessmentAnswer,
  recordViolation,
} from "../../../services/api";
import { redirectToStudentDashboard } from "../../../lib/inPersonEnrollmentUi";

// ─── Constants ─────────────────────────────────────────────
const MAX_VIOLATIONS = 3;

const LEVELS = [
  { key: "beginner", label: "Step 1", color: "#2e7d32" },
  { key: "intermediate", label: "Step 2", color: "#f9a825" },
  { key: "advanced", label: "Step 3", color: "#c62828" },
];

// Backend returns level strings as "Beginner" / "Intermediate" / "Advanced".
// Normalise to lowercase so they match the LEVELS keys above.
const normaliseLevel = (value) =>
  value == null ? value : String(value).toLowerCase();

const ANSWER_COLORS = [
  { bg: "#a3151e" },
  { bg: "#0e4d94" },
  { bg: "#8a6600" },
  { bg: "#1a6208" },
];

const ANSWER_SHAPES = [
  <polygon key="tri" points="12,3 21,21 3,21" />,
  <polygon key="dia" points="12,2 22,12 12,22 2,12" />,
  <circle key="cir" cx="12" cy="12" r="9" />,
  <rect key="sq" x="3" y="3" width="18" height="18" rx="3" />,
];

const swipeVariants = {
  enter: () => ({
    scale: 0.92,
    opacity: 0,
    y: 20,
  }),
  center: {
    x: 0,
    y: 0,
    scale: 1,
    rotate: 0,
    opacity: 1,
    transition: { type: "spring", stiffness: 300, damping: 28 },
  },
  exit: (d) => ({
    x: d > 0 ? 350 : -350,
    rotate: d > 0 ? 18 : -18,
    opacity: 0,
    scale: 0.9,
    transition: { duration: 0.4, ease: [0.36, 0, 0.66, -0.56] },
  }),
};

// ─── Fullscreen helpers ────────────────────────────────────
function requestFullscreen() {
  const el = document.documentElement;
  const rfs =
    el.requestFullscreen ||
    el.webkitRequestFullscreen ||
    el.msRequestFullscreen;
  if (rfs) {
    rfs.call(el).catch(() => {
      // Fallback: ask parent to do it
      window.parent.postMessage({ type: 'REQUEST_FULLSCREEN' }, '*');
    });
  } else {
    window.parent.postMessage({ type: 'REQUEST_FULLSCREEN' }, '*');
  }
}

function exitFullscreen() {
  const efs =
    document.exitFullscreen ||
    document.webkitExitFullscreen ||
    document.msExitFullscreen;
  if (efs && document.fullscreenElement) {
    efs.call(document).catch(() => {
      window.parent.postMessage({ type: 'EXIT_FULLSCREEN' }, '*');
    });
  } else {
    window.parent.postMessage({ type: 'EXIT_FULLSCREEN' }, '*');
  }
}

function isFullscreen() {
  return !!(
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.msFullscreenElement
  );
}

// ─── Violation Warning Overlay ─────────────────────────────
function ViolationOverlay({ violations, maxViolations, onResume }) {
  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm"
    >
      <motion.div
        initial={{ scale: 0.8, y: 20 }}
        animate={{ scale: 1, y: 0 }}
        className="bg-[#1a1a2e] rounded-2xl p-8 max-w-sm w-full mx-4 text-center border border-red-500/30 shadow-2xl"
      >
        <div className="w-16 h-16 rounded-full bg-red-500/20 flex items-center justify-center mx-auto mb-5">
          <FiAlertTriangle size={32} className="text-red-400" />
        </div>
        <h2 className="text-xl font-black text-white mb-2">
          Tab Switch Detected
        </h2>
        <p className="text-white/60 text-sm mb-4">
          Leaving the quiz screen is not allowed during the assessment. Repeated
          violations may result in automatic submission.
        </p>
        <div className="flex items-center justify-center gap-1.5 mb-6">
          {Array.from({ length: maxViolations }).map((_, i) => (
            <div
              key={i}
              className="w-3 h-3 rounded-full"
              style={{
                background:
                  i < violations ? "#ef4444" : "rgba(255,255,255,0.15)",
              }}
            />
          ))}
        </div>
        <p className="text-red-400 text-xs font-bold mb-5">
          Warning {violations} of {maxViolations}
        </p>
        <button
          onClick={onResume}
          className="w-full py-3.5 rounded-lg bg-red-500 hover:bg-red-600 text-white font-bold text-sm transition-colors flex items-center justify-center gap-2"
        >
          <FiMaximize size={14} />
          Return to Quiz
        </button>
      </motion.div>
    </motion.div>
  );
}

function LevelTransition({
  currentLevel,
  nextLevel,
  score,
  total,
  onComplete,
}) {
  const [progress, setProgress] = useState(0);
  const AUTO_ADVANCE_MS = 3500;

  useEffect(() => {
    const start = Date.now();
    const interval = setInterval(() => {
      const elapsed = Date.now() - start;
      const pct = Math.min(elapsed / AUTO_ADVANCE_MS, 1);
      setProgress(pct);
      if (pct >= 1) {
        clearInterval(interval);
        onComplete();
      }
    }, 30);
    return () => clearInterval(interval);
  }, [onComplete]);

  return (
    <motion.div
      key="level-transition"
      initial={{ opacity: 0, scale: 0.9 }}
      animate={{ opacity: 1, scale: 1 }}
      exit={{ opacity: 0, scale: 0.9 }}
      className="w-full max-w-md text-center"
    >
      <motion.div
        initial={{ scale: 0 }}
        animate={{ scale: 1 }}
        transition={{ type: "spring", delay: 0.1 }}
        className="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6"
        style={{ background: currentLevel.color }}
      >
        <FiCheck size={40} className="text-white" />
      </motion.div>

      <motion.h2
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="text-2xl sm:text-3xl font-black text-white mb-2"
      >
        {score}/{total} — Nice work!
      </motion.h2>

      <motion.p
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.35 }}
        className="text-white/60 text-sm sm:text-base mb-8"
      >
        Moving on to the next step
      </motion.p>

      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.5 }}
        className="max-w-xs mx-auto"
      >
        <div className="w-full h-1.5 rounded-full bg-white/15 overflow-hidden">
          <div
            className="h-full rounded-full transition-none"
            style={{
              width: `${progress * 100}%`,
              background: nextLevel.color,
            }}
          />
        </div>
        <p className="text-white/30 text-xs mt-2">
          Starting next step...
        </p>
      </motion.div>
    </motion.div>
  );
}

export default function QuizPage({ params }) {
  const { id } = React.use(params);
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get("token");

  // Quiz flow state
  const [started, setStarted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Proctoring state
  const [violations, setViolations] = useState(0);
  const [showViolation, setShowViolation] = useState(false);
  const violationsRef = useRef(0);

  const violationCooldownRef = useRef(false); // debounce to prevent double-counting

  // Current question from API
  const [question, setQuestion] = useState(null);
  const [currentLevel, setCurrentLevel] = useState("beginner");
  const [selected, setSelected] = useState(null);
  const [submittingAnswer, setSubmittingAnswer] = useState(false);
  const [lastResult, setLastResult] = useState(null); // { is_correct, ... }
  const [score, setScore] = useState(0);
  const [levelScores, setLevelScores] = useState({});
  const [passedLevels, setPassedLevels] = useState({});
  const [answered, setAnswered] = useState({}); // { level: { qIndex: true/false } }
  const [levelTotals, setLevelTotals] = useState({}); // { level: totalQuestions }
  const [dir, setDir] = useState(1);
  const [showLevelEnd, setShowLevelEnd] = useState(false);
  const [assessmentComplete, setAssessmentComplete] = useState(false);
  const [overallLevel, setOverallLevel] = useState(null);
  const [endedByViolation, setEndedByViolation] = useState(false);
  const [timeLeft, setTimeLeft] = useState(0);
  const timerRef = useRef(null);

  // Fetch first question from API
  const loadFirstQuestion = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetchAssessmentQuestions(id, token);

      if (response?.status === "success" && response?.question) {
        const q = response.question;
        const qLevel = normaliseLevel(q.level) || "beginner";
        setQuestion(q);
        setCurrentLevel(qLevel);
        setTimeLeft(q.time_remaining_seconds || 900);
        if (q.total_level_questions) {
          setLevelTotals((p) => ({
            ...p,
            [qLevel]: q.total_level_questions,
          }));
        }
        if (typeof response?.violation_count === 'number') {
          violationsRef.current = response.violation_count;
          setViolations(response.violation_count);
        }
        return true;
      } else if (
        response?.status === "completed" ||
        response?.assessment_completed
      ) {
        setAssessmentComplete(true);
        setOverallLevel(
          normaliseLevel(response?.user_overall_level || response?.user_level) || null,
        );
        setShowLevelEnd(true);
        return true;
      } else {
        setError(response?.message || "Failed to load assessment.");
        return false;
      }
    } catch (err) {
      console.error("Error fetching assessment:", err);
      const apiMessage = err?.response?.data?.message;
      setError(
        apiMessage || "Failed to load assessment questions. Please try again.",
      );
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Timer
  useEffect(() => {
    if (
      started &&
      !showLevelEnd &&
      !loading &&
      !assessmentComplete &&
      question
    ) {
      timerRef.current = setInterval(() => {
        setTimeLeft((prev) => {
          if (prev <= 1) {
            clearInterval(timerRef.current);
            setLevelScores((p) => ({ ...p, [currentLevel]: score }));
            setShowLevelEnd(true);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    } else {
      clearInterval(timerRef.current);
    }
    return () => clearInterval(timerRef.current);
  }, [
    started,
    showLevelEnd,
    loading,
    assessmentComplete,
    question,
    currentLevel,
    score,
  ]);

  // Use refs to avoid stale closures in event handlers
  const currentLevelRef = useRef(currentLevel);
  const scoreRef = useRef(score);
  const assessmentCompleteRef = useRef(assessmentComplete);
  const startedRef = useRef(started);
  const questionRef = useRef(question);

  useEffect(() => {
    currentLevelRef.current = currentLevel;
  }, [currentLevel]);
  useEffect(() => {
    scoreRef.current = score;
  }, [score]);
  useEffect(() => {
    assessmentCompleteRef.current = assessmentComplete;
  }, [assessmentComplete]);
  useEffect(() => {
    startedRef.current = started;
  }, [started]);
  useEffect(() => {
    questionRef.current = question;
  }, [question]);

  // ── Proctoring: detect tab switches & fullscreen exits ──
  useEffect(() => {
    if (!started || assessmentComplete) return;

    const addViolation = () => {
      // Debounce: prevent double-counting when Escape triggers both
      // fullscreenchange and visibilitychange in quick succession
      if (violationCooldownRef.current) return;
      violationCooldownRef.current = true;
      setTimeout(() => {
        violationCooldownRef.current = false;
      }, 500);

      violationsRef.current += 1;
      const count = violationsRef.current;
      setViolations(count);
      setShowViolation(true);

      // Record violation to server
      recordViolation(id, count, token)
        .then((result) => {
          if (count >= MAX_VIOLATIONS) {
            setOverallLevel(normaliseLevel(result?.user_overall_level) || null);
          }
        })
        .catch(() => { });

      if (count >= MAX_VIOLATIONS) {
        // Auto-submit: mark assessment as complete on client
        setLevelScores((p) => ({
          ...p,
          [currentLevelRef.current]: scoreRef.current,
        }));
        setEndedByViolation(true);
        setAssessmentComplete(true);
        setShowLevelEnd(true);
        setShowViolation(false);
        exitFullscreen();
      }
    };

    const handleVisibilityChange = () => {
      if (
        document.hidden &&
        startedRef.current &&
        !assessmentCompleteRef.current
      ) {
        addViolation();
      }
    };

    const handleFullscreenChange = () => {
      if (
        !isFullscreen() &&
        startedRef.current &&
        !assessmentCompleteRef.current
      ) {
        addViolation();
      }
    };

    // Prevent right-click context menu during quiz
    const handleContextMenu = (e) => {
      e.preventDefault();
    };

    // Prevent common keyboard shortcuts for switching/devtools
    const handleKeyDown = (e) => {
      // Block F11 (fullscreen toggle), F12 (devtools)
      if (e.key === "F11" || e.key === "F12") {
        e.preventDefault();
      }
      // Block Ctrl/Cmd+Shift+I (devtools), Ctrl/Cmd+Shift+J (console)
      if (
        (e.ctrlKey || e.metaKey) &&
        e.shiftKey &&
        (e.key === "I" || e.key === "J" || e.key === "C")
      ) {
        e.preventDefault();
      }
      // Block Ctrl/Cmd+U (view source)
      if ((e.ctrlKey || e.metaKey) && e.key === "u") {
        e.preventDefault();
      }
    };

    const handleViolationMessage = (e) => {
      if (
        e.data?.type === "VIOLATION_TRIGGERED" &&
        startedRef.current &&
        !assessmentCompleteRef.current
      ) {
        addViolation();
      }
    };

    document.addEventListener("visibilitychange", handleVisibilityChange);
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
    document.addEventListener("contextmenu", handleContextMenu);
    document.addEventListener("keydown", handleKeyDown);
    window.addEventListener("message", handleViolationMessage);

    return () => {
      document.removeEventListener("visibilitychange", handleVisibilityChange);
      document.removeEventListener("fullscreenchange", handleFullscreenChange);
      document.removeEventListener(
        "webkitfullscreenchange",
        handleFullscreenChange,
      );
      document.removeEventListener("contextmenu", handleContextMenu);
      document.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("message", handleViolationMessage);
    };
  }, [started, assessmentComplete, id, token]);

  // ── Exit fullscreen when assessment completes ──
  useEffect(() => {
    if (assessmentComplete) {
      exitFullscreen();
      window.parent?.postMessage({ type: 'ASSESSMENT_COMPLETE' }, '*');
    }
  }, [assessmentComplete]);

  // ── Resume from violation: re-enter fullscreen ──
  const handleResumeFromViolation = useCallback(() => {
    setShowViolation(false);
    requestFullscreen();
  }, []);

  const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, "0")}`;
  };

  const lvl = LEVELS.find((l) => l.key === currentLevel) || LEVELS[0];
  const lvlIdx = LEVELS.findIndex((l) => l.key === currentLevel);

  // Parse options from the question object: { option1: "...", option2: "...", ... }
  const getOptions = (q) => {
    if (!q?.options) return [];
    if (Array.isArray(q.options)) return q.options;
    // Object format: { option1: "val", option2: "val", ... }
    return Object.entries(q.options)
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([, value]) => value);
  };

  const progress = question?.progress || 0;
  const totalQs =
    levelTotals[currentLevel] || question?.total_level_questions || 10;
  const options = React.useMemo(
    () => (question ? getOptions(question) : []),
    [question],
  );

  // Answer a question
  const handleAnswer = useCallback(
    async (idx) => {
      if (selected !== null || submittingAnswer || !question) return;
      setSelected(idx);
      setSubmittingAnswer(true);

      const answerValue = options[idx];
      const questionProgress = question.progress || 0;

      try {
        const result = await submitAssessmentAnswer(
          id,
          question.id,
          answerValue,
          token,
        );
        const correct = result?.is_correct === true;

        setLastResult(result);
        if (correct) setScore((s) => s + 1);
        setAnswered((p) => ({
          ...p,
          [currentLevel]: {
            ...(p[currentLevel] || {}),
            [questionProgress - 1]: correct,
          },
        }));

        setTimeout(() => {
          if (result?.assessment_completed) {
            // Entire assessment is done
            const finalScore = correct ? score + 1 : score;
            setLevelScores((p) => ({ ...p, [currentLevel]: finalScore }));
            setOverallLevel(normaliseLevel(result?.user_overall_level) || null);
            setAssessmentComplete(true);
            setShowLevelEnd(true);
            setSelected(null);
            setLastResult(null);
            setSubmittingAnswer(false);
          } else if (result?.level_complete) {
            // Current level is done, show transition
            const finalScore = correct ? score + 1 : score;
            setLevelScores((p) => ({ ...p, [currentLevel]: finalScore }));
            setPassedLevels((p) => ({
              ...p,
              [currentLevel]: result?.passed_level || false,
            }));
            setShowLevelEnd(true);
            setSelected(null);
            setLastResult(null);
            setSubmittingAnswer(false);

            // Store next question for after the transition
            if (result?.next_question) {
              const nq = result.next_question;
              const nqLevel = normaliseLevel(nq.level);
              setQuestion({ ...nq, level: nqLevel });
              if (nq.total_level_questions && nqLevel) {
                setLevelTotals((p) => ({
                  ...p,
                  [nqLevel]: nq.total_level_questions,
                }));
              }
            }
          } else if (result?.next_question) {
            // Next question in the same level
            const nextQ = result.next_question;
            const nextLevel = normaliseLevel(nextQ.level);
            setDir(correct ? 1 : -1);
            setQuestion({ ...nextQ, level: nextLevel });
            if (nextQ.time_remaining_seconds) {
              setTimeLeft(nextQ.time_remaining_seconds);
            }
            if (nextQ.total_level_questions && nextLevel) {
              setLevelTotals((p) => ({
                ...p,
                [nextLevel]: nextQ.total_level_questions,
              }));
            }
            // Update level if it changed
            if (nextLevel && nextLevel !== currentLevel) {
              setCurrentLevel(nextLevel);
            }
            setSelected(null);
            setLastResult(null);
            setSubmittingAnswer(false);
          } else {
            // Fallback: just reset selection
            setSelected(null);
            setLastResult(null);
            setSubmittingAnswer(false);
          }
        }, 1100);
      } catch (err) {
        console.error("Error submitting answer:", err);
        // On error, just advance
        setTimeout(() => {
          setSelected(null);
          setLastResult(null);
          setSubmittingAnswer(false);
        }, 1100);
      }
    },
    [
      selected,
      submittingAnswer,
      question,
      options,
      score,
      currentLevel,
      id,
      token,
    ],
  );

  // Move to next level after transition
  const goNextLevel = useCallback(() => {
    if (question) {
      const nextLevelKey =
        normaliseLevel(question.level) || LEVELS[lvlIdx + 1]?.key;
      setCurrentLevel(nextLevelKey || "intermediate");
      setScore(0);
      setShowLevelEnd(false);
      setDir(1);
      if (question.time_remaining_seconds) {
        setTimeLeft(question.time_remaining_seconds);
      }
    }
  }, [question, lvlIdx]);

  // Get assessed level label
  const getAssessedLevelLabel = () => {
    if (overallLevel) {
      const found = LEVELS.find((l) => l.key === overallLevel.toLowerCase());
      return found || { label: overallLevel, color: "#6b7280" };
    }
    // Fallback: highest passed level
    for (let i = LEVELS.length - 1; i >= 0; i--) {
      if (passedLevels[LEVELS[i].key]) return LEVELS[i];
    }
    return null;
  };

  // Shared bg — use absolute + z-0 inside each `relative min-h-screen` root (not fixed + negative z).
  // Fixed + -z-10 often paints behind the document canvas in iframes / embedded views, so the image
  // disappears and white "Level" copy reads as missing design.
  const BG = (
    <div className="pointer-events-none absolute inset-0 z-0">
      <Image
        src="/images/level/level.jpg"
        alt=""
        fill
        className="object-cover"
        sizes="100vw"
        priority
      />
      <div className="absolute inset-0 bg-black/60" aria-hidden />
    </div>
  );

  // ═══════════════════════════════════════════════════════════
  // LANDING
  // ═══════════════════════════════════════════════════════════
  if (!started) {
    return (
      <div className="min-h-screen relative flex items-center justify-center overflow-hidden">
        {BG}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          className="relative z-10 text-center px-6 max-w-md w-full"
        >
          <motion.div
            initial={{ scale: 0, rotate: -10 }}
            animate={{ scale: 1, rotate: 3 }}
            transition={{ type: "spring", delay: 0.2 }}
            className="w-24 h-24 rounded-xl bg-[#f9a825] flex items-center justify-center mx-auto mb-8 shadow-2xl"
          >
            <FiAward size={44} className="text-white" />
          </motion.div>

          <h1 className="text-4xl md:text-5xl font-black text-white mb-3 tracking-tight">
            Level <span className="text-[#f9a825]">Assessment</span>
          </h1>

          {/* <p className="text-white/60 mb-10 max-w-xs mx-auto">
            3 levels. Find out where you stand.
          </p> */}

          {error && (
            <div className="flex items-center gap-2 px-4 py-3 bg-red-500/20 border border-red-500/30 rounded-xl mb-6">
              <FiAlertCircle className="w-4 h-4 text-red-400 flex-shrink-0" />
              <p className="text-sm text-red-300">{error}</p>
            </div>
          )}

          <motion.button
            whileHover={{ scale: 1.03 }}
            whileTap={{ scale: 0.97 }}
            onClick={async () => {
              const success = await loadFirstQuestion();
              if (success) {
                setStarted(true);
                // Request fullscreen after starting — if it fails, quiz still works
                // but proctoring will only rely on visibility change detection
                try {
                  const el = document.documentElement;
                  const rfs =
                    el.requestFullscreen ||
                    el.webkitRequestFullscreen ||
                    el.msRequestFullscreen;
                  if (rfs) {
                    rfs.call(el).catch(() => {
                      // Fallback: ask parent to do it
                      window.parent.postMessage({ type: 'REQUEST_FULLSCREEN' }, '*');
                    });
                  } else {
                    window.parent.postMessage({ type: 'REQUEST_FULLSCREEN' }, '*');
                  }
                } catch {
                  // Fullscreen denied — quiz continues without fullscreen enforcement
                }
              }
            }}
            disabled={loading}
            className="w-full py-4 rounded-lg bg-[#f9a825] text-[#121212] font-bold text-lg shadow-xl hover:bg-[#f57f17] transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {loading ? (
              <>
                <FiLoader className="w-5 h-5 animate-spin" />
                Loading...
              </>
            ) : (
              "Start Assessment"
            )}
          </motion.button>

          <button
            onClick={() => window.location.href = process.env.NEXT_PUBLIC_PORTAL_URL || '/'}
            className="mt-5 text-sm text-white/40 hover:text-white/80 transition-colors"
          >
            <FiHome className="inline mr-1 -mt-0.5" size={14} /> Back to Home
          </button>
        </motion.div>
      </div>
    );
  }

  // ═══════════════════════════════════════════════════════════
  // LOADING / NO QUESTION
  // ═══════════════════════════════════════════════════════════
  if (loading || (!question && !assessmentComplete)) {
    return (
      <div className="min-h-screen relative flex items-center justify-center overflow-hidden">
        {BG}
        <div className="text-center">
          <FiLoader className="w-10 h-10 text-[#f9a825] animate-spin mx-auto mb-4" />
          <p className="text-white/60 text-sm">Loading questions...</p>
        </div>
      </div>
    );
  }

  // ═══════════════════════════════════════════════════════════
  // QUIZ VIEW
  // ═══════════════════════════════════════════════════════════
  const levelEndScore = levelScores[currentLevel] ?? score;
  const levelPassed = passedLevels[currentLevel] || false;
  const nextLevelExists = question?.level && question.level !== currentLevel;
  const hasNextLevel =
    !assessmentComplete &&
    (nextLevelExists || (lvlIdx < LEVELS.length - 1 && levelPassed));
  const assessedLevel = getAssessedLevelLabel();

  return (
    <div className="min-h-screen relative flex flex-col overflow-x-hidden">
      {BG}

      {/* ── Violation Warning Overlay ── */}
      <AnimatePresence>
        {showViolation && !assessmentComplete && (
          <ViolationOverlay
            violations={violations}
            maxViolations={MAX_VIOLATIONS}
            onResume={handleResumeFromViolation}
          />
        )}
      </AnimatePresence>

      {/* ── Top Bar ── */}
      <div className="relative z-20 px-4 pt-4 pb-3">
        <div className="max-w-[30rem] mx-auto">
          {/* Level progress pills */}
          <div className="flex items-center gap-1 sm:gap-1.5 mb-3">
            {LEVELS.map((level, li) => {
              const isActive = currentLevel === level.key;
              const isPassed = passedLevels[level.key] || false;
              const isLocked =
                li > 0 && !passedLevels[LEVELS[li - 1].key] && !isActive;

              return (
                <React.Fragment key={level.key}>
                  {li > 0 && (
                    <div
                      className="w-4 sm:w-6 h-0.5 rounded-full"
                      style={{
                        background: isPassed
                          ? level.color
                          : "rgba(255,255,255,0.15)",
                      }}
                    />
                  )}
                  <div
                    className="flex items-center gap-1.5 px-3 py-1.5 sm:py-2 rounded-lg text-xs font-bold transition-all flex-1 justify-center"
                    style={{
                      background: isActive
                        ? `${level.color}25`
                        : isPassed
                          ? `${level.color}15`
                          : "rgba(255,255,255,0.06)",
                      border: isActive
                        ? `2px solid ${level.color}`
                        : isPassed
                          ? `2px solid ${level.color}40`
                          : "2px solid rgba(255,255,255,0.08)",
                      color: isLocked
                        ? "rgba(255,255,255,0.25)"
                        : isActive
                          ? "white"
                          : isPassed
                            ? level.color
                            : "rgba(255,255,255,0.45)",
                    }}
                  >
                    {isLocked ? (
                      <FiLock size={11} />
                    ) : isPassed ? (
                      <FiCheck size={11} />
                    ) : (
                      <span
                        className="w-1.5 h-1.5 rounded-full"
                        style={{
                          background: isActive
                            ? level.color
                            : "rgba(255,255,255,0.3)",
                        }}
                      />
                    )}
                    <span className="hidden sm:inline">{level.label}</span>
                    <span className="sm:hidden">{level.label.slice(0, 3)}</span>
                  </div>
                </React.Fragment>
              );
            })}
          </div>

          {/* Progress bar for current level */}
          {!showLevelEnd && (
            <div className="flex gap-1">
              {Array.from({ length: totalQs }).map((_, qi) => {
                const a = (answered[currentLevel] || {})[qi];
                const isCurrent = qi === progress - 1;

                return (
                  <motion.div
                    key={qi}
                    className="flex-1 h-1.5 rounded-full"
                    style={{
                      background:
                        a === true
                          ? "#16a34a"
                          : a === false
                            ? "#ef4444"
                            : isCurrent
                              ? lvl.color
                              : "rgba(255,255,255,0.12)",
                    }}
                    animate={
                      isCurrent ? { opacity: [0.4, 1, 0.4] } : { opacity: 1 }
                    }
                    transition={
                      isCurrent ? { repeat: Infinity, duration: 1.2 } : {}
                    }
                  />
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* ── Main Content ── */}
      <div className="flex-1 flex items-center justify-center px-4 pb-6 relative z-10">
        <AnimatePresence mode="wait" custom={dir}>
          {showLevelEnd ? (
            assessmentComplete ? (
              /* ── Final Results Card ── */
              <motion.div
                key="final-results"
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.9 }}
                className="w-full max-w-md"
              >
                <div className="rounded-xl overflow-hidden shadow-2xl">
                  <div
                    className="p-8 text-center"
                    style={{
                      background: endedByViolation
                        ? "#dc2626"
                        : assessedLevel?.color || lvl.color,
                    }}
                  >
                    <motion.div
                      initial={{ scale: 0 }}
                      animate={{ scale: 1 }}
                      transition={{ type: "spring", delay: 0.15 }}
                      className="w-16 h-16 rounded-xl flex items-center justify-center mb-4 mx-auto"
                      style={{ background: "rgba(255,255,255,0.2)" }}
                    >
                      {endedByViolation ? (
                        <FiAlertTriangle size={36} className="text-white" />
                      ) : (
                        <FiAward size={36} className="text-white" />
                      )}
                    </motion.div>
                    <h2 className="text-2xl font-black text-white mb-1">
                      {endedByViolation
                        ? "Assessment Terminated"
                        : "Assessment Complete"}
                    </h2>
                    <p className="text-white/70 text-sm">
                      {endedByViolation
                        ? "Your quiz was ended due to exceeding the maximum number of tab-switch violations."
                        : "Your skills have been evaluated"}
                    </p>
                  </div>

                  <div className="bg-white p-6 text-center">
                    {/* Per-level score summary */}
                    <div className="space-y-3 mb-6">
                      {LEVELS.map((level) => {
                        const total = levelTotals[level.key];
                        const lvlScore = levelScores[level.key];
                        const lvlAnswered = answered[level.key];
                        if (total == null && lvlScore == null && !lvlAnswered)
                          return null;
                        const displayTotal =
                          total || Object.keys(lvlAnswered || {}).length || 0;
                        const displayScore = lvlScore ?? 0;
                        const passed = passedLevels[level.key];
                        return (
                          <div
                            key={level.key}
                            className="flex items-center gap-3 p-3 rounded-lg"
                            style={{
                              background: `${level.color}10`,
                              border: `1px solid ${level.color}25`,
                            }}
                          >
                            <div
                              className="w-8 h-8 rounded-lg flex items-center justify-center"
                              style={{ background: level.color }}
                            >
                              {passed ? (
                                <FiCheck size={14} className="text-white" />
                              ) : (
                                <FiX size={14} className="text-white" />
                              )}
                            </div>
                            <div className="flex-1 text-left">
                              <p className="text-sm font-bold text-gray-800">
                                {level.label}
                              </p>
                              <div className="flex gap-1 mt-1">
                                {Array.from({ length: displayTotal }).map(
                                  (_, i) => {
                                    const a = (lvlAnswered || {})[i];
                                    return (
                                      <div
                                        key={i}
                                        className="w-4 h-4 rounded flex items-center justify-center"
                                        style={{
                                          background:
                                            a === true
                                              ? "#16a34a"
                                              : a === false
                                                ? "#ef4444"
                                                : "#e5e7eb",
                                        }}
                                      >
                                        {a === true && (
                                          <FiCheck
                                            size={8}
                                            className="text-white"
                                          />
                                        )}
                                        {a === false && (
                                          <FiX
                                            size={8}
                                            className="text-white"
                                          />
                                        )}
                                      </div>
                                    );
                                  },
                                )}
                              </div>
                            </div>
                            <span
                              className="text-sm font-bold"
                              style={{ color: level.color }}
                            >
                              {displayScore}/{displayTotal}
                            </span>
                          </div>
                        );
                      })}
                    </div>

                    <div className="space-y-2.5">
                      <button
                        onClick={() => redirectToStudentDashboard()}
                        className="w-full py-3.5 rounded-lg font-bold text-sm flex items-center justify-center gap-2 shadow-lg active:scale-[0.98] transition-colors bg-[#f9a825] hover:bg-[#f57f17] text-[#121212]"
                      >
                        Proceed to Dashboard <FiArrowRight size={16} />
                      </button>
                    </div>
                  </div>
                </div>
              </motion.div>
            ) : hasNextLevel ? (
              /* ── Level Transition ── */
              <LevelTransition
                key={`transition-${currentLevel}`}
                currentLevel={lvl}
                nextLevel={
                  LEVELS[lvlIdx + 1] || {
                    label: question?.level || "Next",
                    color: "#f9a825",
                  }
                }
                score={levelEndScore}
                total={totalQs}
                onComplete={goNextLevel}
              />
            ) : (
              /* ── Level failed — no next level ── */
              <motion.div
                key="level-end-fail"
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.9 }}
                className="w-full max-w-md"
              >
                <div className="rounded-xl overflow-hidden shadow-2xl">
                  <div
                    className="p-8 text-center"
                    style={{ background: lvl.color }}
                  >
                    <motion.div
                      initial={{ scale: 0 }}
                      animate={{ scale: 1 }}
                      transition={{ type: "spring", delay: 0.15 }}
                      className="w-16 h-16 rounded-xl flex items-center justify-center mb-4 mx-auto"
                      style={{ background: "rgba(255,255,255,0.2)" }}
                    >
                      <FiBarChart2 size={36} className="text-white" />
                    </motion.div>
                    <h2 className="text-2xl font-black text-white mb-1">
                      Level Complete
                    </h2>
                    <p className="text-white/70 text-sm">
                      You scored {levelEndScore}/
                      {levelTotals[currentLevel] || totalQs}
                    </p>
                  </div>
                  <div className="bg-white p-6 text-center">
                    <div className="mb-6">
                      <span className="text-5xl font-black text-gray-900">
                        {levelEndScore}
                      </span>
                      <span className="text-xl text-gray-400 font-bold">
                        /{levelTotals[currentLevel] || totalQs}
                      </span>
                    </div>
                    <div className="space-y-2.5">
                      <button
                        onClick={() => redirectToStudentDashboard()}
                        className="w-full py-3.5 rounded-lg font-bold text-sm flex items-center justify-center gap-2 shadow-lg active:scale-[0.98] transition-colors bg-[#f9a825] hover:bg-[#f57f17] text-[#121212]"
                      >
                        Proceed to Dashboard <FiArrowRight size={16} />
                      </button>
                    </div>
                  </div>
                </div>
              </motion.div>
            )
          ) : question ? (
            /* ── Question Card ── */
            <motion.div
              key={`q-${question.id}`}
              custom={dir}
              variants={swipeVariants}
              initial="enter"
              animate="center"
              exit="exit"
              style={{ transformOrigin: "bottom center" }}
              className="w-full max-w-[30rem] relative overflow-hidden rounded-xl"
            >
              <div className="bg-[#f7f7f7] rounded-xl shadow-2xl relative">
                {/* Question area */}
                <div className="px-6 py-8 text-center bg-gray-100/80">
                  <div className="flex items-center justify-between mb-3">
                    <p
                      className="text-xs font-bold uppercase tracking-widest"
                      style={{ color: lvl.color }}
                    >
                      {lvl.label} &middot; {progress}/{totalQs}
                    </p>
                    <div
                      className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold ${timeLeft <= 60
                          ? "bg-red-100 text-red-600"
                          : timeLeft <= 300
                            ? "bg-yellow-100 text-yellow-700"
                            : "bg-gray-200 text-gray-600"
                        }`}
                    >
                      <FiClock size={11} />
                      <span className="font-mono">{formatTime(timeLeft)}</span>
                    </div>
                  </div>
                  <h2 className="text-xl md:text-2xl font-black text-gray-900 leading-snug">
                    {question.question}
                  </h2>
                </div>

                {/* Answer grid */}
                <div
                  className={`grid ${options.length <= 2 ? "grid-cols-1" : "grid-cols-2"} gap-3 p-4 sm:p-5`}
                >
                  {options.map((opt, idx) => {
                    const isSelected = selected === idx;
                    const revealed = selected !== null && lastResult !== null;
                    const isCorrectAnswer =
                      revealed && isSelected && lastResult?.is_correct === true;
                    const isWrongAnswer =
                      revealed &&
                      isSelected &&
                      lastResult?.is_correct === false;
                    const labels = ["A", "B", "C", "D", "E", "F"];

                    let bg = ANSWER_COLORS[idx % ANSWER_COLORS.length].bg;
                    let animScale = 1;
                    if (revealed) {
                      if (isCorrectAnswer) {
                        bg = "#16a34a";
                      } else if (isWrongAnswer) {
                        bg = "#6b7280";
                        animScale = 0.97;
                      } else {
                        animScale = 0.95;
                      }
                    }

                    return (
                      <motion.button
                        key={idx}
                        whileTap={!revealed ? { scale: 0.97 } : {}}
                        animate={{
                          scale: animScale,
                          opacity: revealed && !isSelected ? 0.35 : 1,
                        }}
                        onClick={() => handleAnswer(idx)}
                        disabled={revealed || submittingAnswer}
                        className="relative rounded-xl p-4 sm:p-5 min-h-[110px] sm:min-h-[120px] flex flex-col items-center justify-center gap-2 text-center cursor-pointer hover:brightness-110 transition-[filter] border border-white/10 overflow-hidden"
                        style={{
                          background: `linear-gradient(145deg, ${bg}, ${bg}dd)`,
                          boxShadow: `0 4px 12px ${bg}40, 0 1px 3px rgba(0,0,0,0.2)`,
                        }}
                      >
                        <svg
                          viewBox="0 0 24 24"
                          fill="rgba(255,255,255,0.07)"
                          className="absolute -bottom-3 -right-3 w-20 h-20 sm:w-24 sm:h-24 pointer-events-none"
                        >
                          {ANSWER_SHAPES[idx % ANSWER_SHAPES.length]}
                        </svg>
                        <svg
                          viewBox="0 0 24 24"
                          fill="rgba(255,255,255,0.04)"
                          className="absolute -top-4 -left-4 w-16 h-16 sm:w-20 sm:h-20 pointer-events-none rotate-12"
                        >
                          {ANSWER_SHAPES[idx % ANSWER_SHAPES.length]}
                        </svg>
                        <span className="absolute top-2 left-2.5 text-[10px] sm:text-xs font-black text-white/50 bg-white/10 w-5 h-5 sm:w-6 sm:h-6 rounded-md flex items-center justify-center">
                          {labels[idx] || idx + 1}
                        </span>
                        <span className="text-white font-bold text-xs sm:text-sm leading-snug relative z-[1]">
                          {opt}
                        </span>
                        {revealed && isCorrectAnswer && (
                          <motion.div
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            className="absolute top-2 right-2 w-6 h-6 rounded-full bg-white/25 flex items-center justify-center"
                          >
                            <FiCheck size={14} className="text-white" />
                          </motion.div>
                        )}
                        {revealed && isWrongAnswer && (
                          <motion.div
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            className="absolute top-2 right-2 w-6 h-6 rounded-full bg-white/25 flex items-center justify-center"
                          >
                            <FiX size={14} className="text-white" />
                          </motion.div>
                        )}
                      </motion.button>
                    );
                  })}
                </div>

                {/* ── Result banner ── */}
                <AnimatePresence>
                  {selected !== null &&
                    lastResult !== null &&
                    (() => {
                      const wasCorrect = lastResult.is_correct === true;
                      return (
                        <motion.div
                          key="banner"
                          initial={{ x: "-110%" }}
                          animate={{ x: "0%" }}
                          exit={{ x: "110%" }}
                          transition={{
                            type: "spring",
                            stiffness: 280,
                            damping: 26,
                          }}
                          className="absolute inset-0 flex items-center justify-center pointer-events-none"
                          style={{ zIndex: 20 }}
                        >
                          <div
                            className="w-[150%] py-5 flex items-center justify-center gap-4 shadow-2xl"
                            style={{
                              background: wasCorrect ? "#16a34a" : "#ef4444",
                              transform: "rotate(-3deg)",
                            }}
                          >
                            <motion.div
                              initial={{ scale: 0, rotate: -90 }}
                              animate={{ scale: 1, rotate: 0 }}
                              transition={{
                                delay: 0.12,
                                type: "spring",
                                stiffness: 400,
                              }}
                              className="w-11 h-11 rounded-full border-[3px] border-white/40 flex items-center justify-center"
                            >
                              {wasCorrect ? (
                                <FiCheck
                                  size={24}
                                  className="text-white"
                                  strokeWidth={3}
                                />
                              ) : (
                                <FiX
                                  size={24}
                                  className="text-white"
                                  strokeWidth={3}
                                />
                              )}
                            </motion.div>
                            <motion.span
                              initial={{ opacity: 0, x: 40 }}
                              animate={{ opacity: 1, x: 0 }}
                              transition={{ delay: 0.18 }}
                              className="text-white text-3xl sm:text-4xl font-black italic tracking-tight"
                            >
                              {wasCorrect ? "Correct!" : "Incorrect"}
                            </motion.span>
                          </div>
                        </motion.div>
                      );
                    })()}
                </AnimatePresence>
              </div>
            </motion.div>
          ) : null}
        </AnimatePresence>
      </div>
    </div>
  );
}
