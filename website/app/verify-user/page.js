"use client";

import React, { useState, useEffect, Suspense, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useSearchParams } from "next/navigation";
import dynamic from "next/dynamic";
import {
  FiShield,
  FiCamera,
  FiCheckCircle,
  FiAlertCircle,
  FiLoader,
  FiArrowLeft,
  FiArrowRight,
  FiCreditCard,
} from "react-icons/fi";
import { submitGhanaCardVerification } from "../../services/api";
import { preloadLivenessAssets } from "../../lib/livenessPreload";

const LivenessDetection = dynamic(
  () => import("../../components/verification/LivenessDetection"),
  { ssr: false },
);

const GHANA_CARD_PIN_REGEX = /^GHA-\d{9}-\d$/;

const STEPS = [
  { key: "pin", label: "Ghana Card PIN" },
  { key: "liveness", label: "Face Verification" },
  { key: "review", label: "Review & Submit" },
];

const VERIFICATION_MESSAGE_SOURCE = "omcp-verification";

function formatGhanaCardPinInput(rawValue) {
  const digits = (rawValue || "").replace(/\D/g, "").slice(0, 10);
  const firstNine = digits.slice(0, 9);
  const lastDigit = digits.slice(9, 10);

  if (!firstNine) return "GHA-";
  if (!lastDigit) return `GHA-${firstNine}`;
  return `GHA-${firstNine}-${lastDigit}`;
}

function VerifyUserContent() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const isEmbedMode = searchParams.get("embed") === "1";
  const parentOrigin = searchParams.get("parent_origin");

  // Auth state
  const [authError, setAuthError] = useState(null);

  const sendParentMessage = useCallback((type, message, data = null) => {

    if (typeof window === "undefined" || window.parent === window) return;

    const targetOrigin = "*";

    window.parent.postMessage(
      {
        source: VERIFICATION_MESSAGE_SOURCE,
        type,
        message,
        data,
      },
      targetOrigin,
    );
  }, [parentOrigin]);

  // Flow state
  const [step, setStep] = useState("pin");
  const [pin, setPin] = useState("");
  const [pinError, setPinError] = useState(null);
  const [facePhoto, setFacePhoto] = useState(null);
  const [facePhotoPreview, setFacePhotoPreview] = useState(null);

  // Submit state
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  useEffect(() => {
    if (typeof window === "undefined" || window.parent === window) {
      window.document.body.innerHTML = "<p>Please access this page from the student portal.</p>";
      return;
    };

    const emitHeight = () => {
      const doc = document.documentElement;
      const body = document.body;
      const measuredHeight = Math.max(
        doc?.scrollHeight || 0,
        doc?.offsetHeight || 0,
        body?.scrollHeight || 0,
        body?.offsetHeight || 0,
      );
      sendParentMessage("iframe_resize", null, { height: measuredHeight });
    };

    emitHeight();
    const timeoutId = window.setTimeout(emitHeight, 250);

    window.addEventListener("resize", emitHeight);

    let resizeObserver;
    if (typeof ResizeObserver !== "undefined" && document.body) {
      resizeObserver = new ResizeObserver(() => emitHeight());
      resizeObserver.observe(document.body);
    }

    return () => {
      window.clearTimeout(timeoutId);
      window.removeEventListener("resize", emitHeight);
      if (resizeObserver) resizeObserver.disconnect();
    };
  }, [sendParentMessage, step, isSubmitting, isSubmitted, submitError, authError]);

  // Validate token on mount
  useEffect(() => {
    if (!token) {
      const message = "No authentication token provided. Please access this page from the student portal.";
      setAuthError(message);
      sendParentMessage("verification_failed", message);
    }
  }, [token, sendParentMessage]);

  // Warm MediaPipe WASM + face-landmarker model while the user fills in the PIN,
  // so the liveness step starts instantly.
  useEffect(() => {
    if (step === "pin") preloadLivenessAssets();
  }, [step]);

  // Handle initial PIN load from URL only once
  useEffect(() => {
    const ghCardNumber = searchParams.get("ghcard_number");
    if (ghCardNumber) {
      setPin(formatGhanaCardPinInput(ghCardNumber));
    }
  }, []);

  const currentStepIndex = STEPS.findIndex((s) => s.key === step);

  const handlePinSubmit = () => {
    if (!pin.trim()) {
      setPinError("Please enter your Ghana Card PIN.");
      return;
    }
    if (!GHANA_CARD_PIN_REGEX.test(pin.trim())) {
      setPinError("Invalid format. Use: GHA-123456789-0");
      return;
    }
    setPinError(null);
    setStep("liveness");
  };

  const handleLivenessComplete = (photo) => {
    setFacePhoto(photo);
    setFacePhotoPreview(URL.createObjectURL(photo));
    setStep("review");
  };

  const handleSubmit = async () => {
    if (!facePhoto) return;

    setIsSubmitting(true);
    setSubmitError(null);

    try {
      const formData = new FormData();
      formData.append("image", facePhoto, "face_photo.jpg");
      if (pin.trim()) {
        formData.append("pin", pin.trim());
      }

      const response = await submitGhanaCardVerification(formData, token);

      if (response.success) {
        setIsSubmitted(true);
        sendParentMessage("verification_submitted", response.message, response);
      } else {
        const message = response.message || "Verification submission failed. Please try again.";
        setSubmitError(message);
        sendParentMessage("verification_failed", message, response);
      }
    } catch (error) {
      const message = error.response?.data?.message || error.response?.data?.errors?.image?.[0] || "Failed to submit verification. Please try again.";
      setSubmitError(message);
      sendParentMessage("verification_failed", message, error.response?.data ?? null);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStartOver = () => {
    setStep("pin");
    setPin("");
    setPinError(null);
    setFacePhoto(null);
    setFacePhotoPreview(null);
    setSubmitError(null);
    setIsSubmitted(false);
  };

  // No token — show error
  if (authError) {
    return (
      <div className={`${isEmbedMode ? "h-full bg-white p-3 sm:p-4" : "min-h-screen bg-gray-50 p-4"} flex items-center justify-center`}>
        <div className="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <FiAlertCircle className="w-8 h-8 text-red-500" />
          </div>
          <h2 className="text-lg font-bold text-gray-900 mb-2">Authentication Required</h2>
          <p className="text-sm text-gray-500">{authError}</p>
        </div>
      </div>
    );
  }

  // Success state
  if (isSubmitted) {
    return (
      <div className={`${isEmbedMode ? "h-full bg-white p-3 sm:p-4" : "min-h-screen bg-gray-50 p-4"} flex items-center justify-center`}>
        <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full text-center">
          <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-5">
            <FiCheckCircle className="w-10 h-10 text-green-500" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">Verification Submitted</h2>
          <p className="text-sm text-gray-500 max-w-sm mx-auto">
            Your Ghana Card verification has been queued. You will be notified once processed.
          </p>
          {/* show a restart if verification failed */}
          {submitError && (
            <div className="mt-4">
              <p className="text-sm text-red-500">{submitError}</p>
              <button
                onClick={handleStartOver}
                className="mt-4 bg-blue-500 text-white px-4 py-2 rounded-lg"
              >
                Restart
              </button>
            </div>
          )}
        </motion.div>
      </div>
    );
  }

  return (
    <div className={`${isEmbedMode ? "h-full bg-white p-2 sm:p-4" : "min-h-screen bg-gray-50 p-4"} flex items-center justify-center`}>
      <div className={`bg-white w-full max-w-2xl overflow-hidden ${isEmbedMode ? "rounded-xl border border-gray-200 shadow-sm" : "rounded-2xl shadow-lg"}`}>
        {/* Header */}
        <div className="bg-white border-b border-gray-100 px-6 py-4">
          <div className="flex items-center space-x-3 mb-4">
            <div className="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
              <FiShield className="w-5 h-5 text-yellow-600" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-gray-900">Identity Verification</h1>
              <p className="text-xs text-gray-500">Verify your identity with your Ghana Card</p>
            </div>
          </div>

          {/* Step indicator */}
          <div className="flex items-center space-x-2">
            {STEPS.map((s, i) => (
              <React.Fragment key={s.key}>
                <div className="flex items-center space-x-2">
                  <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${i < currentStepIndex ? "bg-green-500 text-white"
                    : i === currentStepIndex ? "bg-yellow-400 text-gray-900"
                      : "bg-gray-100 text-gray-400 "
                    }`}>
                    {i < currentStepIndex ? (
                      <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                      </svg>
                    ) : (
                      i + 1
                    )}
                  </div>
                  <span className={`text-xs font-medium hidden sm:block ${i === currentStepIndex ? "text-gray-900"
                    : i < currentStepIndex ? "text-green-600"
                      : "text-gray-400"
                    }`}>
                    {s.label}
                  </span>
                </div>
                {i < STEPS.length - 1 && (
                  <div className={`flex-1 h-px ${i < currentStepIndex ? "bg-green-500" : "bg-gray-200"}`} />
                )}
              </React.Fragment>
            ))}
          </div>
        </div>

        {/* Body */}
        <div className="p-6">
          <AnimatePresence mode="wait">
            {/* Step 1: Ghana Card PIN */}
            {step === "pin" && (
              <motion.div key="pin" initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }} className="space-y-6">
                <div className="text-center">
                  <div className="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <FiCreditCard className="w-7 h-7 text-yellow-600" />
                  </div>
                  <h3 className="text-base font-semibold text-gray-900">Enter your Ghana Card PIN</h3>
                  <p className="text-sm text-gray-500 mt-1">Find your PIN on the front of your Ghana Card</p>
                </div>

                <div className="max-w-sm mx-auto">
                  <label htmlFor="ghana-pin" className="block text-sm font-medium text-gray-700 mb-2">
                    Ghana Card PIN
                  </label>
                  <input
                    id="ghana-pin"
                    type="text"
                    value={pin}
                    onChange={(e) => {
                      setPin(formatGhanaCardPinInput(e.target.value));
                      if (pinError) setPinError(null);
                    }}
                    placeholder="GHA-123456789-0"
                    className={`w-full px-4 py-3 border-2 rounded-xl text-center text-lg font-mono tracking-wider focus:outline-none focus:ring-0 transition-colors ${pinError ? "border-red-300 focus:border-red-500" : "border-gray-200 focus:border-yellow-400"
                      }`}
                    maxLength={15}
                    autoComplete="off"
                    inputMode="numeric"
                  />
                  {pinError && <p className="text-xs text-red-600 mt-2 text-center">{pinError}</p>}

                  <div className="bg-blue-50 rounded-lg p-3 mt-4">
                    <p className="text-xs text-blue-700">
                      <strong>Format:</strong> GHA-XXXXXXXXX-X &nbsp; &nbsp; (where X is a digit)
                    </p>
                  </div>
                </div>

                <div className="flex justify-end pt-2">
                  <motion.button
                    onClick={handlePinSubmit}
                    className="flex items-center space-x-2 px-6 py-3 bg-yellow-400 text-gray-900 text-sm font-semibold rounded-full hover:bg-yellow-500 shadow-md hover:shadow-lg transition-all"
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                  >
                    <span>Continue</span>
                    <FiArrowRight className="w-4 h-4" />
                  </motion.button>
                </div>
              </motion.div>
            )}

            {/* Step 2: Liveness Detection */}
            {step === "liveness" && (
              <motion.div key="liveness" initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }}>
                <LivenessDetection
                  onComplete={handleLivenessComplete}
                  onCancel={() => setStep("pin")}
                />
              </motion.div>
            )}

            {/* Step 3: Review & Submit */}
            {step === "review" && (
              <motion.div key="review" initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }} className="space-y-6">
                <div className="text-center">
                  <h3 className="text-base font-semibold text-gray-900">Review your submission</h3>
                  <p className="text-sm text-gray-500 mt-1">Confirm your details before submitting for verification.</p>
                </div>

                {/* PIN display */}
                <div className="bg-gray-50 rounded-xl p-4">
                  <div className="flex items-center space-x-2 mb-1">
                    <FiCreditCard className="w-4 h-4 text-yellow-600" />
                    <span className="text-sm font-medium text-gray-700">Ghana Card PIN</span>
                  </div>
                  <p className="text-lg font-mono tracking-wider text-gray-900 ml-6">{pin}</p>
                </div>

                {/* Face photo preview */}
                <div className="space-y-2">
                  <div className="flex items-center space-x-2">
                    <FiCamera className="w-4 h-4 text-yellow-600" />
                    <span className="text-sm font-medium text-gray-700">Face Photo</span>
                  </div>
                  <div className="aspect-[4/3] max-w-xs mx-auto rounded-xl overflow-hidden border-2 border-gray-100 bg-gray-50">
                    {facePhotoPreview && (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={facePhotoPreview} alt="Face photo" className="w-full h-full object-cover" />
                    )}
                  </div>
                </div>

                {/* Submit error */}
                {submitError && (
                  <div className="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div className="flex items-start space-x-2">
                      <FiAlertCircle className="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
                      <p className="text-sm text-red-700">{submitError}</p>
                    </div>
                  </div>
                )}

                {/* Actions */}
                <div className="flex space-x-3 pt-2">
                  <button
                    onClick={handleStartOver}
                    disabled={isSubmitting}
                    className="flex items-center justify-center space-x-2 flex-1 py-3 px-4 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors disabled:opacity-50"
                  >
                    <FiArrowLeft className="w-4 h-4" />
                    <span>Start Over</span>
                  </button>
                  <motion.button
                    onClick={handleSubmit}
                    disabled={isSubmitting}
                    className="flex items-center justify-center space-x-2 flex-1 py-3 px-4 text-sm font-bold text-gray-900 bg-yellow-400 hover:bg-yellow-500 rounded-full shadow-md hover:shadow-lg disabled:opacity-50 transition-all"
                    whileHover={!isSubmitting ? { scale: 1.02 } : {}}
                    whileTap={!isSubmitting ? { scale: 0.98 } : {}}
                  >
                    {isSubmitting ? (
                      <span className="flex items-center space-x-2">
                        <motion.span
                          className="w-4 h-4 border-2 border-gray-900/30 border-t-gray-900 rounded-full inline-block"
                          animate={{ rotate: 360 }}
                          transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
                        />
                        <span>Submitting...</span>
                      </span>
                    ) : (
                      <>
                        <FiShield className="w-4 h-4" />
                        <span>Submit Verification</span>
                      </>
                    )}
                  </motion.button>
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>
    </div>
  );
}

export default function VerifyUserPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <FiLoader className="w-8 h-8 text-yellow-400 animate-spin mx-auto mb-3" />
            <p className="text-sm text-gray-500">Loading...</p>
          </div>
        </div>
      }
    >
      <VerifyUserContent />
    </Suspense>
  );
}
