"use client";

import React, { useRef, useEffect, useState, useCallback, useMemo } from "react";
import { motion } from "framer-motion";
import { FaceLandmarker, DrawingUtils } from "@mediapipe/tasks-vision";
import {
  FACE_LANDMARKER_MODEL_URL,
  getFilesetResolverPromise,
  prefetchFaceLandmarkerModel,
} from "../../lib/livenessPreload";

const CHALLENGE_LABELS = {
  center_face: "Position your face in the oval",
  blink: "Please blink your eyes",
  turn_left: "Slowly turn your head left",
  turn_right: "Slowly turn your head right",
  capture_photo: "Hold still for your photo",
};

const EAR_BLINK_THRESHOLD = 0.15;
const HEAD_TURN_THRESHOLD = 15;
const FACE_DETECTION_HOLD_MS = 1500;
const CAPTURE_HOLD_MS = 3000;
const CAPTURE_YAW_TOLERANCE = 8;
const CAPTURE_CENTER_TOLERANCE = 0.15;

const LANDMARK_NOSE_TIP = 1;
const LANDMARK_MOUTH_LEFT = 61;
const LANDMARK_MOUTH_RIGHT = 291;
const LANDMARK_CHIN = 152;
const LANDMARK_FOREHEAD = 10;

const LEFT_EYE_EAR = [33, 160, 158, 133, 153, 144];
const RIGHT_EYE_EAR = [362, 385, 387, 263, 373, 380];

const CAPTURE_FACE_MIN_RATIO = 0.40;
const CAPTURE_FACE_MAX_RATIO = 0.55;

function estimateFaceSize(landmarks) {
  const chin = landmarks[152];
  const forehead = landmarks[10];
  const leftCheek = landmarks[234];
  const rightCheek = landmarks[454];
  const height = Math.abs(chin.y - forehead.y);
  const width = Math.abs(rightCheek.x - leftCheek.x);
  return { width, height };
}

function sampleBrightness(video) {
  const canvas = document.createElement("canvas");
  canvas.width = 80;
  canvas.height = 60;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(video, 0, 0, 80, 60);
  const { data } = ctx.getImageData(0, 0, 80, 60);
  let sum = 0;
  for (let i = 0; i < data.length; i += 4) {
    sum += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
  }
  return sum / (data.length / 4);
}

function computeEAR(landmarks, indices) {
  const p1 = landmarks[indices[0]];
  const p2 = landmarks[indices[1]];
  const p3 = landmarks[indices[2]];
  const p4 = landmarks[indices[3]];
  const p5 = landmarks[indices[4]];
  const p6 = landmarks[indices[5]];

  const vertical1 = Math.sqrt(Math.pow(p2.x - p6.x, 2) + Math.pow(p2.y - p6.y, 2));
  const vertical2 = Math.sqrt(Math.pow(p3.x - p5.x, 2) + Math.pow(p3.y - p5.y, 2));
  const horizontal = Math.sqrt(Math.pow(p1.x - p4.x, 2) + Math.pow(p1.y - p4.y, 2));

  return (vertical1 + vertical2) / (2.0 * horizontal);
}

function estimateYaw(landmarks) {
  const noseTip = landmarks[1];
  const leftCheek = landmarks[234];
  const rightCheek = landmarks[454];

  const midX = (leftCheek.x + rightCheek.x) / 2;
  const faceWidth = Math.abs(rightCheek.x - leftCheek.x);

  if (faceWidth === 0) return 0;
  return ((noseTip.x - midX) / faceWidth) * 90;
}

function getFaceDistanceHint(isFaceTooClose, isFaceTooFar) {
  if (isFaceTooClose) return "Move back a little";
  if (isFaceTooFar) return "Move closer to the camera";
  return null;
}

export default function LivenessDetection({ onComplete, onCancel }) {
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const faceLandmarkerRef = useRef(null);
  const animationFrameRef = useRef(0);
  const streamRef = useRef(null);
  const baselineEARRef = useRef(null);
  const challengeStartRef = useRef(0);
  const captureStartRef = useRef(null);
  const brightnessFilterRef = useRef("brightness(1)");
  const [videoFilter, setVideoFilter] = useState("brightness(1)");
  const holdStillRef = useRef(null);


  const [isLoading, setIsLoading] = useState(false);
  const [isModelReady, setIsModelReady] = useState(false);
  const [error, setError] = useState(null);
  const [cameraPermission, setCameraPermission] = useState("prompt");
  const [hasStartedCamera, setHasStartedCamera] = useState(false);
  const [currentChallenge, setCurrentChallenge] = useState("center_face");
  const [challengeProgress, setChallengeProgress] = useState(0);
  const [completedChallenges, setCompletedChallenges] = useState([]);
  const [faceDetected, setFaceDetected] = useState(false);
  const [captureCountdown, setCaptureCountdown] = useState(null);
  const [landmarkQuality, setLandmarkQuality] = useState({
    eyesVisible: false,
    noseVisible: false,
    mouthVisible: false,
    centered: false,
    straight: false,
  });

  const challenges = useMemo(
    () => ["center_face", "blink", "turn_left", "turn_right", "capture_photo"],
    []
  );

  const capturePhoto = useCallback(() => {
    const video = videoRef.current;
    if (!video) return;

    const captureCanvas = document.createElement("canvas");
    captureCanvas.width = video.videoWidth;
    captureCanvas.height = video.videoHeight;

    const ctx = captureCanvas.getContext("2d");
    if (!ctx) return;

    ctx.drawImage(video, 0, 0);
    captureCanvas.toBlob(
      (blob) => {
        if (blob) onComplete(blob);
      },
      "image/jpeg",
      0.9
    );
  }, [onComplete]);

  const advanceChallenge = useCallback(() => {
    const currentIndex = challenges.indexOf(currentChallenge);
    setCompletedChallenges((prev) => [...prev, currentChallenge]);
    setChallengeProgress(0);
    baselineEARRef.current = null;

    if (currentIndex < challenges.length - 1) {
      setCurrentChallenge(challenges[currentIndex + 1]);
      challengeStartRef.current = Date.now();
    } else {
      capturePhoto();
    }
  }, [currentChallenge, challenges, capturePhoto]);

  const startLivenessDetection = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    // Kick off model prep in parallel with camera permission prompt.
    // Both calls are idempotent and cached — safe to call even if preload ran earlier.
    prefetchFaceLandmarkerModel();
    const landmarkerPromise = getFilesetResolverPromise().then((filesetResolver) =>
      FaceLandmarker.createFromOptions(filesetResolver, {
        baseOptions: {
          modelAssetPath: FACE_LANDMARKER_MODEL_URL,
          delegate: "GPU",
        },
        runningMode: "VIDEO",
        numFaces: 1,
        outputFacialTransformationMatrixes: false,
        outputFaceBlendshapes: false,
      }),
    );

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "user", width: 640, height: 480 },
      });

      setCameraPermission("granted");
      setHasStartedCamera(true);
      streamRef.current = stream;

      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        await videoRef.current.play();
      }

      // Camera is live — drop the full-screen spinner immediately so the user
      // sees their own face while the landmarker finishes loading in the
      // background. The detection loop no-ops until faceLandmarkerRef is set.
      setIsLoading(false);

      const faceLandmarker = await landmarkerPromise;

      // First detectForVideo call pays shader-compile cost. Warm it against the
      // live video so the first real frame runs at full speed.
      try {
        if (videoRef.current && videoRef.current.readyState >= 2) {
          faceLandmarker.detectForVideo(videoRef.current, performance.now());
        }
      } catch {
        // Warmup is best-effort.
      }

      faceLandmarkerRef.current = faceLandmarker;
      challengeStartRef.current = Date.now();
      setIsModelReady(true);
    } catch (err) {
      console.error("Failed to initialize liveness detection:", err);
      // Swallow rejection on the unused landmarker to avoid an unhandled rejection.
      landmarkerPromise.catch(() => { });
      setCameraPermission("denied");
      setError("Camera access is required to continue verification. Please allow camera permission and try again.");
      setHasStartedCamera(false);
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    return () => {
      if (animationFrameRef.current) cancelAnimationFrame(animationFrameRef.current);
      if (streamRef.current) streamRef.current.getTracks().forEach((t) => t.stop());
      if (faceLandmarkerRef.current) faceLandmarkerRef.current.close();
    };
  }, []);

  useEffect(() => {
    holdStillRef.current = null;
    captureStartRef.current = null;
    setCaptureCountdown(null);
    setChallengeProgress(0);
  }, [currentChallenge]);

  const processChallenge = useCallback(
    (landmarks) => {
      switch (currentChallenge) {
        case "center_face": {
          const nose = landmarks[1];
          const isCentered = nose.x > 0.3 && nose.x < 0.7 && nose.y > 0.3 && nose.y < 0.7;
          if (isCentered) {
            const elapsed = Date.now() - challengeStartRef.current;
            const progress = Math.min((elapsed / FACE_DETECTION_HOLD_MS) * 100, 100);
            setChallengeProgress(progress);
            if (progress >= 100) advanceChallenge();
          } else {
            challengeStartRef.current = Date.now();
            setChallengeProgress(0);
          }
          break;
        }

        case "blink": {
          const leftEAR = computeEAR(landmarks, LEFT_EYE_EAR);
          const rightEAR = computeEAR(landmarks, RIGHT_EYE_EAR);
          const avgEAR = (leftEAR + rightEAR) / 2;

          if (baselineEARRef.current === null) {
            baselineEARRef.current = avgEAR;
            break;
          }

          if (avgEAR < EAR_BLINK_THRESHOLD) {
            setChallengeProgress(100);
            advanceChallenge();
          } else {
            setChallengeProgress(
              Math.min(
                ((baselineEARRef.current - avgEAR) / (baselineEARRef.current - EAR_BLINK_THRESHOLD)) * 100,
                90
              )
            );
          }
          break;
        }

        case "turn_left": {
          const yaw = estimateYaw(landmarks);
          if (yaw > HEAD_TURN_THRESHOLD) {
            setChallengeProgress(100);
            advanceChallenge();
          } else {
            setChallengeProgress(Math.max(0, Math.min((yaw / HEAD_TURN_THRESHOLD) * 100, 90)));
          }
          break;
        }

        case "turn_right": {
          const yaw = estimateYaw(landmarks);
          if (yaw < -HEAD_TURN_THRESHOLD) {
            setChallengeProgress(100);
            advanceChallenge();
          } else {
            setChallengeProgress(Math.max(0, Math.min((Math.abs(yaw) / HEAD_TURN_THRESHOLD) * 100, 90)));
          }
          break;
        }

        case "capture_photo": {
          const nose = landmarks[LANDMARK_NOSE_TIP];
          const leftEye = landmarks[33];
          const rightEye = landmarks[263];
          const mouthLeft = landmarks[LANDMARK_MOUTH_LEFT];
          const mouthRight = landmarks[LANDMARK_MOUTH_RIGHT];
          const chin = landmarks[LANDMARK_CHIN];
          const forehead = landmarks[LANDMARK_FOREHEAD];
          const yaw = estimateYaw(landmarks);

          const isInFrame = (pt) => pt.x > 0.1 && pt.x < 0.9 && pt.y > 0.05 && pt.y < 0.95;

          const eyesVisible = isInFrame(leftEye) && isInFrame(rightEye);
          const noseVisible = isInFrame(nose);
          const mouthVisible = isInFrame(mouthLeft) && isInFrame(mouthRight);
          const centered = Math.abs(nose.x - 0.5) < CAPTURE_CENTER_TOLERANCE && Math.abs(nose.y - 0.5) < 0.2;
          const straight = Math.abs(yaw) < CAPTURE_YAW_TOLERANCE;
          const fullFaceVisible = eyesVisible && noseVisible && mouthVisible && isInFrame(chin) && isInFrame(forehead);

          const { width: faceWidth, height: faceHeight } = estimateFaceSize(landmarks);

          const faceSizeRatio = Math.max(faceWidth, faceHeight);

          const isFaceTooClose = faceSizeRatio > CAPTURE_FACE_MAX_RATIO;
          const isFaceTooFar = faceSizeRatio < CAPTURE_FACE_MIN_RATIO;
          const isFaceDistanceOk = !isFaceTooClose && !isFaceTooFar;

          setLandmarkQuality({ eyesVisible, noseVisible, mouthVisible, centered, straight, isFaceDistanceOk, isFaceTooClose });

          const allGood = fullFaceVisible && centered && straight && isFaceDistanceOk;

          if (allGood) {
            if (!captureStartRef.current) captureStartRef.current = Date.now();
            const elapsed = Date.now() - captureStartRef.current;
            const remaining = Math.max(0, Math.ceil((CAPTURE_HOLD_MS - elapsed) / 1000));
            setCaptureCountdown(remaining);
            const progress = Math.min((elapsed / CAPTURE_HOLD_MS) * 100, 100);
            setChallengeProgress(progress);
            if (progress >= 100) {
              if (isFaceDistanceOk && fullFaceVisible && centered && straight) {
                capturePhoto();
              } else {
                // Conditions changed on the capture frame — reset and wait
                captureStartRef.current = null;
                setCaptureCountdown(null);
                setChallengeProgress(0);
              }
            };
          } else {
            captureStartRef.current = null;
            setCaptureCountdown(null);
            setChallengeProgress(0);
          }
          break;
        }
      }
    },
    [currentChallenge, advanceChallenge, capturePhoto]
  );

  // Detection loop
  useEffect(() => {
    if (isLoading || error || !hasStartedCamera) return;

    let lastTime = -1;

    function detect() {
      const video = videoRef.current;
      const canvas = canvasRef.current;
      const faceLandmarker = faceLandmarkerRef.current;

      if (!video || !canvas || !faceLandmarker || video.readyState < 2) {
        animationFrameRef.current = requestAnimationFrame(detect);
        return;
      }

      const now = performance.now();
      if (now === lastTime) {
        animationFrameRef.current = requestAnimationFrame(detect);
        return;
      }
      lastTime = now;

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      if (Math.round(now / 100) % 6 === 0) {
        const brightness = sampleBrightness(video);
        const boost = brightness < 110
          ? Math.min(2.2, 110 / Math.max(brightness, 20))
          : 1;
        const newFilter = `brightness(${boost.toFixed(2)})`;
        if (newFilter !== brightnessFilterRef.current) {
          brightnessFilterRef.current = newFilter;
          setVideoFilter(newFilter);
        }
      }

      const ctx = canvas.getContext("2d");
      if (!ctx) {
        animationFrameRef.current = requestAnimationFrame(detect);
        return;
      }

      ctx.save();
      ctx.scale(-1, 1);
      ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
      ctx.restore();

      const results = faceLandmarker.detectForVideo(video, now);

      if (results.faceLandmarks && results.faceLandmarks.length > 0) {
        setFaceDetected(true);
        const landmarks = results.faceLandmarks[0];

        const drawingUtils = new DrawingUtils(ctx);
        const mirroredLandmarks = landmarks.map((l) => ({ ...l, x: 1 - l.x }));
        drawingUtils.drawConnectors(mirroredLandmarks, FaceLandmarker.FACE_LANDMARKS_TESSELATION, {
          color: "rgba(249, 168, 37, 0.2)",
          lineWidth: 0.5,
        });
        drawingUtils.drawConnectors(mirroredLandmarks, FaceLandmarker.FACE_LANDMARKS_FACE_OVAL, {
          color: "rgba(249, 168, 37, 0.6)",
          lineWidth: 1.5,
        });

        processChallenge(landmarks);
      } else {
        setFaceDetected(false);
      }

      animationFrameRef.current = requestAnimationFrame(detect);
    }

    animationFrameRef.current = requestAnimationFrame(detect);

    return () => {
      if (animationFrameRef.current) cancelAnimationFrame(animationFrameRef.current);
    };
  }, [isLoading, error, processChallenge, hasStartedCamera]);

  if (!hasStartedCamera && !isLoading) {
    return (
      <div className="rounded-xl border border-gray-200 bg-gray-50 p-6 text-center space-y-3">
        <h4 className="text-base font-semibold text-gray-900">Camera Permission Required</h4>
        <p className="text-sm text-gray-600">
          Liveness verification requires camera access. Click below to allow camera use.
        </p>
        {cameraPermission === "denied" && (
          <p className="text-sm text-red-600">
            Camera permission was denied. You cannot proceed without approval.
          </p>
        )}
        <div className="flex items-center justify-center gap-3 pt-1">
          <button
            onClick={startLivenessDetection}
            className="px-4 py-2 rounded-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-sm font-semibold"
          >
            Allow Camera and Continue
          </button>
          <button
            onClick={onCancel}
            className="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium"
          >
            Go Back
          </button>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg className="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <p className="text-gray-700 mb-4">{error}</p>
        <button onClick={onCancel} className="text-yellow-500 hover:text-yellow-600 font-medium">
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Challenge instruction */}
      <motion.div key={currentChallenge} initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} className="text-center">
        {/* Animated challenge icons */}
        <div className="flex justify-center mb-3" style={{ perspective: 200 }}>
          {currentChallenge === "center_face" && (
            <motion.div animate={{ scale: [1, 1.06, 1] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut" }}>
              <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <motion.circle cx="40" cy="40" r="36" stroke="#F9A825" strokeWidth="2" fill="none" strokeDasharray="6 4" animate={{ rotate: 360 }} transition={{ duration: 8, repeat: Infinity, ease: "linear" }} style={{ transformOrigin: "40px 40px" }} />
                <ellipse cx="40" cy="40" rx="22" ry="28" stroke="#F9A825" strokeWidth="2" fill="none" opacity="0.4" />
                <ellipse cx="32" cy="35" rx="4" ry="2.5" fill="#F9A825" opacity="0.7" />
                <ellipse cx="48" cy="35" rx="4" ry="2.5" fill="#F9A825" opacity="0.7" />
                <path d="M40 40 L38 46 L42 46" stroke="#F9A825" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round" opacity="0.5" />
                <path d="M33 52 C36 56 44 56 47 52" stroke="#F9A825" strokeWidth="2" strokeLinecap="round" fill="none" opacity="0.6" />
                <path d="M10 22 L10 10 L22 10" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.8" />
                <path d="M58 10 L70 10 L70 22" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.8" />
                <path d="M70 58 L70 70 L58 70" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.8" />
                <path d="M22 70 L10 70 L10 58" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.8" />
              </svg>
            </motion.div>
          )}

          {currentChallenge === "blink" && (
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
              <ellipse cx="40" cy="40" rx="26" ry="32" stroke="#F9A825" strokeWidth="2" fill="none" opacity="0.3" />
              <path d="M40 40 L38 46 L42 46" stroke="#F9A825" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round" opacity="0.4" />
              <path d="M32 54 C35 58 45 58 48 54" stroke="#F9A825" strokeWidth="1.5" strokeLinecap="round" fill="none" opacity="0.4" />
              <motion.ellipse cx="30" cy="34" rx="8" ry="5" stroke="#F9A825" strokeWidth="2" fill="none" animate={{ ry: [5, 0.3, 0.3, 5] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
              <motion.circle cx="30" cy="34" r="2.5" fill="#F9A825" animate={{ scaleY: [1, 0, 0, 1], opacity: [1, 0, 0, 1] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
              <motion.line x1="22" y1="34" x2="38" y2="34" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" animate={{ opacity: [0, 1, 1, 0] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
              <motion.ellipse cx="50" cy="34" rx="8" ry="5" stroke="#F9A825" strokeWidth="2" fill="none" animate={{ ry: [5, 0.3, 0.3, 5] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
              <motion.circle cx="50" cy="34" r="2.5" fill="#F9A825" animate={{ scaleY: [1, 0, 0, 1], opacity: [1, 0, 0, 1] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
              <motion.line x1="42" y1="34" x2="58" y2="34" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" animate={{ opacity: [0, 1, 1, 0] }} transition={{ duration: 2.5, repeat: Infinity, ease: "easeInOut", times: [0, 0.12, 0.18, 0.3] }} />
            </svg>
          )}

          {currentChallenge === "turn_left" && (
            <div className="relative w-[120px] h-[80px]">
              <motion.div className="absolute left-0 top-1/2 -translate-y-1/2" animate={{ x: [20, 0, 20], opacity: [0, 1, 0] }} transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut" }}>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 6 L6 12 L14 18" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
              </motion.div>
              <motion.div className="absolute left-0 top-1/2 -translate-y-1/2" animate={{ x: [30, 10, 30], opacity: [0, 0.5, 0] }} transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut", delay: 0.15 }}>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 6 L6 12 L14 18" stroke="#F9A825" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
              </motion.div>
              <motion.div className="absolute right-2 top-0" animate={{ rotateY: [0, -30, 0] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} style={{ transformStyle: "preserve-3d" }}>
                <svg width="72" height="80" viewBox="0 0 72 80" fill="none">
                  <ellipse cx="36" cy="40" rx="22" ry="30" stroke="#F9A825" strokeWidth="2" fill="none" />
                  <motion.ellipse cx="27" cy="35" rx="4" ry="3" fill="#F9A825" opacity="0.7" animate={{ cx: [27, 23, 27] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} />
                  <motion.ellipse cx="45" cy="35" rx="4" ry="3" fill="#F9A825" opacity="0.7" animate={{ cx: [45, 39, 45], rx: [4, 2.5, 4] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} />
                  <path d="M29 54 C32 57 40 57 43 54" stroke="#F9A825" strokeWidth="1.5" strokeLinecap="round" fill="none" opacity="0.5" />
                </svg>
              </motion.div>
            </div>
          )}

          {currentChallenge === "turn_right" && (
            <div className="relative w-[120px] h-[80px]">
              <motion.div className="absolute left-2 top-0" animate={{ rotateY: [0, 30, 0] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} style={{ transformStyle: "preserve-3d" }}>
                <svg width="72" height="80" viewBox="0 0 72 80" fill="none">
                  <ellipse cx="36" cy="40" rx="22" ry="30" stroke="#F9A825" strokeWidth="2" fill="none" />
                  <motion.ellipse cx="27" cy="35" rx="4" ry="3" fill="#F9A825" opacity="0.7" animate={{ cx: [27, 33, 27], rx: [4, 2.5, 4] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} />
                  <motion.ellipse cx="45" cy="35" rx="4" ry="3" fill="#F9A825" opacity="0.7" animate={{ cx: [45, 49, 45] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} />
                  <path d="M29 54 C32 57 40 57 43 54" stroke="#F9A825" strokeWidth="1.5" strokeLinecap="round" fill="none" opacity="0.5" />
                </svg>
              </motion.div>
              <motion.div className="absolute right-0 top-1/2 -translate-y-1/2" animate={{ x: [-20, 0, -20], opacity: [0, 1, 0] }} transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut" }}>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M10 6 L18 12 L10 18" stroke="#F9A825" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
              </motion.div>
              <motion.div className="absolute right-0 top-1/2 -translate-y-1/2" animate={{ x: [-30, -10, -30], opacity: [0, 0.5, 0] }} transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut", delay: 0.15 }}>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M10 6 L18 12 L10 18" stroke="#F9A825" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
              </motion.div>
            </div>
          )}

          {currentChallenge === "capture_photo" && (
            <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} transition={{ duration: 0.4 }}>
              <svg width="90" height="90" viewBox="0 0 90 90" fill="none">
                <rect x="15" y="25" width="60" height="44" rx="8" stroke="#F9A825" strokeWidth="2" fill="none" />
                <motion.circle cx="45" cy="47" r="14" stroke="#F9A825" strokeWidth="2" fill="none" animate={{ r: [14, 15, 14] }} transition={{ duration: 1.5, repeat: Infinity, ease: "easeInOut" }} />
                <motion.circle cx="45" cy="47" r="8" fill="#F9A825" opacity="0.15" animate={{ r: [8, 9, 8], opacity: [0.15, 0.25, 0.15] }} transition={{ duration: 1.5, repeat: Infinity, ease: "easeInOut" }} />
                <circle cx="45" cy="47" r="3" fill="#F9A825" opacity="0.5" />
                <motion.rect x="58" y="30" width="8" height="5" rx="2" fill="#F9A825" opacity="0.4" animate={{ opacity: [0.4, 0.8, 0.4] }} transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }} />
                <rect x="24" y="30" width="10" height="6" rx="1.5" stroke="#F9A825" strokeWidth="1.5" fill="none" opacity="0.5" />
                {captureCountdown !== null && captureCountdown > 0 && (
                  <motion.circle cx="45" cy="47" r="18" stroke="#F9A825" strokeWidth="1.5" fill="none" initial={{ r: 14, opacity: 0.8 }} animate={{ r: 22, opacity: 0 }} transition={{ duration: 1, repeat: Infinity, ease: "easeOut" }} />
                )}
              </svg>
            </motion.div>
          )}

        </div>

        <p className="text-lg font-semibold text-gray-900">{CHALLENGE_LABELS[currentChallenge]}</p>
        {!faceDetected && !isLoading && <p className="text-sm text-red-500 mt-1">No face detected - look at the camera</p>}
        {faceDetected && currentChallenge === "center_face" && <p className="text-sm text-yellow-500 mt-1">Hold still...</p>}

        {/* Capture photo: landmark checklist */}
        {currentChallenge === "capture_photo" && faceDetected && (
          <div className="mt-3 space-y-1">
            <div className="flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs">
              <span className={landmarkQuality.eyesVisible ? "text-green-600" : "text-red-500"}>
                {landmarkQuality.eyesVisible ? "\u2713" : "\u2717"} Eyes visible
              </span>
              <span className={landmarkQuality.noseVisible ? "text-green-600" : "text-red-500"}>
                {landmarkQuality.noseVisible ? "\u2713" : "\u2717"} Nose visible
              </span>
              <span className={landmarkQuality.mouthVisible ? "text-green-600" : "text-red-500"}>
                {landmarkQuality.mouthVisible ? "\u2713" : "\u2717"} Mouth visible
              </span>
              <span className={landmarkQuality.centered ? "text-green-600" : "text-red-500"}>
                {landmarkQuality.centered ? "\u2713" : "\u2717"} Centered
              </span>
              <span className={landmarkQuality.isFaceDistanceOk ? "text-green-600" : "text-yellow-500"}>
                {landmarkQuality.isFaceDistanceOk
                  ? "✓ Good distance"
                  : landmarkQuality.isFaceTooClose
                    ? "✗ Move back"
                    : "✗ Move closer"}
              </span>
            </div>
            {captureCountdown !== null && captureCountdown > 0 && (
              <motion.p key={captureCountdown} initial={{ scale: 1.3, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="text-2xl font-bold text-yellow-500 mt-2">
                {captureCountdown}
              </motion.p>
            )}
            {captureCountdown === null && <p className="text-xs text-gray-500 mt-1">Position your face so all checkmarks are green</p>}
          </div>
        )}
      </motion.div>

      {/* Camera view */}
      <div className="relative rounded-2xl overflow-hidden bg-black aspect-[4/3] max-w-lg mx-auto shadow-lg">
        {isLoading && (
          <div className="absolute inset-0 flex items-center justify-center bg-gray-900 z-10">
            <div className="text-center text-white">
              <motion.div className="w-10 h-10 border-2 border-white/30 border-t-white rounded-full mx-auto mb-4" animate={{ rotate: 360 }} transition={{ duration: 1, repeat: Infinity, ease: "linear" }} />
              <p className="text-sm font-medium">Starting camera...</p>
            </div>
          </div>
        )}

        {!isLoading && hasStartedCamera && !isModelReady && (
          <div className="absolute top-3 left-3 z-10 flex items-center space-x-2 bg-black/60 text-white text-xs font-medium px-3 py-1.5 rounded-full backdrop-blur-sm">
            <motion.span
              className="w-3 h-3 border-2 border-white/40 border-t-white rounded-full"
              animate={{ rotate: 360 }}
              transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
            />
            <span>Preparing detection…</span>
          </div>
        )}

        <video ref={videoRef} className="absolute inset-0 w-full h-full object-cover" playsInline muted style={{ transform: "scaleX(-1)", filter: videoFilter }} />
        <canvas ref={canvasRef} className="absolute inset-0 w-full h-full object-cover" />

        {/* Oval guide */}
        <div className="absolute inset-0 pointer-events-none">
          <svg className="w-full h-full" viewBox="0 0 640 480">
            <defs>
              <mask id="oval-mask">
                <rect width="640" height="480" fill="white" />
                <ellipse cx="320" cy="220" rx="175" ry="180" fill="black" />
              </mask>
            </defs>
            <rect width="640" height="480" fill="rgba(0,0,0,0.5)" mask="url(#oval-mask)" />
            <ellipse cx="320" cy="220" rx="175" ry="180" fill="none" stroke={faceDetected ? "#F9A825" : "rgba(255,255,255,0.5)"} strokeWidth="3" strokeDasharray={faceDetected ? "none" : "8 4"} />
          </svg>
        </div>

        {/* Progress bar */}
        <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-black/30">
          <motion.div className="h-full bg-yellow-400" initial={{ width: 0 }} animate={{ width: `${challengeProgress}%` }} transition={{ duration: 0.15 }} />
        </div>

        {/* Challenge progress chips */}
        <div className="absolute top-3 right-3 flex items-center space-x-1.5">
          {challenges.map((c) => (
            <div key={c} className={`w-2.5 h-2.5 rounded-full transition-colors ${completedChallenges.includes(c) ? "bg-green-400" : c === currentChallenge ? "bg-white" : "bg-white/30"}`} />
          ))}
        </div>
      </div>

      {/* Back button */}
      <div className="text-center">
        <button
          onClick={() => {
            if (streamRef.current) streamRef.current.getTracks().forEach((t) => t.stop());
            onCancel();
          }}
          className="text-sm text-gray-500 hover:text-gray-700 font-medium transition-colors"
        >
          Go back
        </button>
      </div>
    </div>
  );
}
