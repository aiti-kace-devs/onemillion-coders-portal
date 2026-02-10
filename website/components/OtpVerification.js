"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  FiMail,
  FiShield,
  FiCheckCircle,
  FiLoader,
  FiAlertCircle,
  FiRefreshCw,
} from "react-icons/fi";
import { sendOtp, verifyOtp, checkOtpStatus } from "../services/pages";

/**
 * OTP Verification component for registration forms.
 *
 * Renders a "Get OTP" button alongside email/phone fields, shows
 * a 6-digit OTP entry with auto-verify, and reports verification
 * status back to the parent. OTP is purely for ownership proof —
 * it is NOT included in registration submission data.
 *
 * @param {Object} props
 * @param {string}  props.email       - Current email field value
 * @param {string}  [props.phone]     - Current phone field value (optional)
 * @param {string}  props.formUuid    - The form UUID for the registration form
 * @param {(verified: boolean) => void} props.onVerified - Callback when verification state changes
 * @param {string}  [props.recaptchaToken] - Optional reCAPTCHA token
 */
const OtpVerification = ({ email, phone, formUuid, onVerified, recaptchaToken }) => {
  // ── State ────────────────────────────────────────
  const [otpState, setOtpState] = useState("idle");
  // idle | sending | sent | verifying | verified | error | expired
  const [otpDigits, setOtpDigits] = useState(["", "", "", "", "", ""]);
  const [errorMessage, setErrorMessage] = useState("");
  const [remainingAttempts, setRemainingAttempts] = useState(null);
  const [countdown, setCountdown] = useState(0);
  const [expiresIn, setExpiresIn] = useState(0);

  // Track the email that was verified so we can reset on change
  const [verifiedEmail, setVerifiedEmail] = useState("");

  const inputRefs = useRef([]);
  const countdownRef = useRef(null);
  const expiryRef = useRef(null);
  const pollingRef = useRef(null);

  // ── Email validation helper ──────────────────────
  const isEmailValid = useCallback((val) => {
    if (!val || typeof val !== "string") return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
  }, []);

  // ── Reset everything when email changes after verification ──
  useEffect(() => {
    if (verifiedEmail && email && email.toLowerCase().trim() !== verifiedEmail.toLowerCase().trim()) {
      // Email changed after successful verification — reset
      setOtpState("idle");
      setOtpDigits(["", "", "", "", "", ""]);
      setErrorMessage("");
      setRemainingAttempts(null);
      setCountdown(0);
      setExpiresIn(0);
      setVerifiedEmail("");
      onVerified(false);
      stopPolling();
    }
  }, [email, verifiedEmail, onVerified]);

  // ── Cleanup on unmount ───────────────────────────
  useEffect(() => {
    return () => {
      clearInterval(countdownRef.current);
      clearInterval(expiryRef.current);
      stopPolling();
    };
  }, []);

  // ── Resend cooldown timer ────────────────────────
  useEffect(() => {
    if (countdown > 0) {
      countdownRef.current = setInterval(() => {
        setCountdown((prev) => {
          if (prev <= 1) {
            clearInterval(countdownRef.current);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }
    return () => clearInterval(countdownRef.current);
  }, [countdown]);

  // ── OTP expiry timer ─────────────────────────────
  useEffect(() => {
    if (expiresIn > 0) {
      expiryRef.current = setInterval(() => {
        setExpiresIn((prev) => {
          if (prev <= 1) {
            clearInterval(expiryRef.current);
            if (otpState === "sent") {
              setOtpState("expired");
              setErrorMessage("OTP has expired. Please request a new one.");
            }
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }
    return () => clearInterval(expiryRef.current);
  }, [expiresIn, otpState]);

  // ── Polling for link-based verification ──────────
  const startPolling = useCallback(
    (emailAddr) => {
      stopPolling();
      pollingRef.current = setInterval(async () => {
        try {
          const res = await checkOtpStatus(emailAddr);
          if (res?.verified) {
            setOtpState("verified");
            setVerifiedEmail(emailAddr);
            onVerified(true);
            stopPolling();
          }
        } catch {
          // Silently continue polling
        }
      }, 4000); // Poll every 4 seconds
    },
    [onVerified]
  );

  const stopPolling = () => {
    if (pollingRef.current) {
      clearInterval(pollingRef.current);
      pollingRef.current = null;
    }
  };

  // ── Send OTP ─────────────────────────────────────
  const handleSendOtp = async () => {
    if (!isEmailValid(email)) return;

    setOtpState("sending");
    setErrorMessage("");
    setOtpDigits(["", "", "", "", "", ""]);
    setRemainingAttempts(null);

    try {
      const payload = {
        email: email.trim(),
        form_uuid: formUuid,
      };
      if (phone && phone.trim().length >= 8) {
        payload.phone = phone.trim();
      }
      if (recaptchaToken) {
        payload.recaptcha_token = recaptchaToken;
      }

      const res = await sendOtp(payload);

      if (res?.success) {
        setOtpState("sent");
        setCountdown(60); // 60-second resend cooldown
        setExpiresIn(res.expires_in || 600);
        // Start polling in case user verifies via email link
        startPolling(email.trim());
        // Auto-focus first OTP input
        setTimeout(() => inputRefs.current[0]?.focus(), 150);
      } else {
        setOtpState("error");
        setErrorMessage(res?.message || "Failed to send OTP. Please try again.");
      }
    } catch (err) {
      setOtpState("error");
      const serverMsg =
        err?.response?.data?.message || "Failed to send verification code. Please try again.";
      const retryAfter = err?.response?.data?.retry_after;
      setErrorMessage(serverMsg);
      if (retryAfter) setCountdown(retryAfter);
    }
  };

  // ── Verify OTP ───────────────────────────────────
  const handleVerifyOtp = useCallback(
    async (digits) => {
      const code = digits.join("");
      if (code.length !== 6) return;

      setOtpState("verifying");
      setErrorMessage("");

      try {
        const res = await verifyOtp({ email: email.trim(), otp: code });

        if (res?.verified || res?.success) {
          setOtpState("verified");
          setVerifiedEmail(email.trim());
          onVerified(true);
          stopPolling();
        } else {
          setOtpState("sent"); // Go back to "sent" so user can retry
          setErrorMessage(res?.message || "Invalid code. Please try again.");
          if (res?.remaining_attempts !== undefined && res.remaining_attempts !== null) {
            setRemainingAttempts(res.remaining_attempts);
          }
          // Clear digits and refocus
          setOtpDigits(["", "", "", "", "", ""]);
          setTimeout(() => inputRefs.current[0]?.focus(), 150);
        }
      } catch (err) {
        setOtpState("sent");
        const msg =
          err?.response?.data?.message || "Verification failed. Please try again.";
        setErrorMessage(msg);
        const remaining = err?.response?.data?.remaining_attempts;
        if (remaining !== undefined && remaining !== null) {
          setRemainingAttempts(remaining);
        }
        setOtpDigits(["", "", "", "", "", ""]);
        setTimeout(() => inputRefs.current[0]?.focus(), 150);
      }
    },
    [email, onVerified]
  );

  // ── OTP input handlers ───────────────────────────
  const handleDigitChange = (index, value) => {
    // Only allow single digit
    const digit = value.replace(/\D/g, "").slice(-1);
    const newDigits = [...otpDigits];
    newDigits[index] = digit;
    setOtpDigits(newDigits);

    // Clear error on input
    if (errorMessage) setErrorMessage("");

    // Auto-advance to next input
    if (digit && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }

    // Auto-verify when all 6 digits are filled
    if (digit && newDigits.every((d) => d !== "")) {
      handleVerifyOtp(newDigits);
    }
  };

  const handleDigitKeyDown = (index, e) => {
    if (e.key === "Backspace" && !otpDigits[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
    if (e.key === "ArrowLeft" && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
    if (e.key === "ArrowRight" && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handlePaste = (e) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
    if (!pasted) return;

    const newDigits = [...otpDigits];
    for (let i = 0; i < 6; i++) {
      newDigits[i] = pasted[i] || "";
    }
    setOtpDigits(newDigits);

    // Focus the next empty or last field
    const nextEmpty = newDigits.findIndex((d) => d === "");
    inputRefs.current[nextEmpty >= 0 ? nextEmpty : 5]?.focus();

    // Auto-verify if all filled
    if (newDigits.every((d) => d !== "")) {
      handleVerifyOtp(newDigits);
    }
  };

  // ── Format time mm:ss ────────────────────────────
  const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, "0")}`;
  };

  // ── Render ───────────────────────────────────────

  // Don't render anything if email is empty
  const emailIsValid = isEmailValid(email);

  return (
    <AnimatePresence mode="wait">
      {/* ═══ VERIFIED STATE ═══ */}
      {otpState === "verified" && (
        <motion.div
          key="verified"
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={{ opacity: 0, height: 0 }}
          transition={{ duration: 0.3 }}
          className="mt-3"
        >
          <div className="flex items-center gap-2 px-4 py-2.5 bg-green-50 border border-green-200 rounded-xl">
            <FiCheckCircle className="w-5 h-5 text-green-600 flex-shrink-0" />
            <span className="text-sm font-medium text-green-700">
              Email verified successfully
            </span>
          </div>
        </motion.div>
      )}

      {/* ═══ IDLE / GET OTP BUTTON ═══ */}
      {otpState === "idle" && (
        <motion.div
          key="idle"
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={{ opacity: 0, height: 0 }}
          transition={{ duration: 0.3 }}
          className="mt-3"
        >
          <button
            type="button"
            onClick={handleSendOtp}
            disabled={!emailIsValid}
            className={`
              inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
              transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1
              ${
                emailIsValid
                  ? "bg-yellow-400 text-gray-900 hover:bg-yellow-500 focus:ring-yellow-400 shadow-sm hover:shadow-md cursor-pointer"
                  : "bg-gray-100 text-gray-400 cursor-not-allowed"
              }
            `}
          >
            <FiMail className="w-4 h-4" />
            Get OTP
          </button>
          {!emailIsValid && email && email.length > 0 && (
            <p className="text-xs text-gray-400 mt-1.5">
              Enter a valid email address to request a verification code
            </p>
          )}
        </motion.div>
      )}

      {/* ═══ SENDING STATE ═══ */}
      {otpState === "sending" && (
        <motion.div
          key="sending"
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={{ opacity: 0, height: 0 }}
          transition={{ duration: 0.3 }}
          className="mt-3"
        >
          <div className="flex items-center gap-2 px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-xl">
            <FiLoader className="w-4 h-4 text-yellow-600 animate-spin" />
            <span className="text-sm text-yellow-700 font-medium">
              Sending verification code...
            </span>
          </div>
        </motion.div>
      )}

      {/* ═══ SENT / OTP ENTRY ═══ */}
      {(otpState === "sent" || otpState === "verifying" || otpState === "expired") && (
        <motion.div
          key="otp-entry"
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={{ opacity: 0, height: 0 }}
          transition={{ duration: 0.35, ease: "easeOut" }}
          className="mt-4"
        >
          <div className="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-2xl p-4 sm:p-5 shadow-sm">
            {/* Header */}
            <div className="flex items-start gap-3 mb-4">
              <div className="w-9 h-9 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <FiShield className="w-5 h-5 text-yellow-700" />
              </div>
              <div className="flex-1 min-w-0">
                <h4 className="text-sm font-semibold text-gray-900">
                  Enter Verification Code
                </h4>
                <p className="text-xs text-gray-500 mt-0.5 leading-relaxed">
                  We sent a 6-digit code to{" "}
                  <span className="font-medium text-gray-700">{email}</span>.
                  Check your inbox{phone ? " (or SMS)" : ""}.
                </p>
              </div>
            </div>

            {/* OTP digit inputs */}
            <div className="flex justify-center gap-2 sm:gap-3 mb-4">
              {otpDigits.map((digit, idx) => (
                <input
                  key={idx}
                  ref={(el) => (inputRefs.current[idx] = el)}
                  type="text"
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  maxLength={1}
                  value={digit}
                  onChange={(e) => handleDigitChange(idx, e.target.value)}
                  onKeyDown={(e) => handleDigitKeyDown(idx, e)}
                  onPaste={idx === 0 ? handlePaste : undefined}
                  disabled={otpState === "verifying"}
                  className={`
                    w-11 h-12 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-bold
                    border-2 rounded-xl transition-all duration-200
                    focus:outline-none focus:ring-2 focus:ring-offset-1
                    disabled:bg-gray-50 disabled:cursor-not-allowed
                    ${
                      errorMessage
                        ? "border-red-300 focus:border-red-500 focus:ring-red-200 text-red-700"
                        : digit
                        ? "border-yellow-400 bg-yellow-50 focus:border-yellow-500 focus:ring-yellow-200 text-gray-900"
                        : "border-gray-300 focus:border-yellow-500 focus:ring-yellow-200 text-gray-900"
                    }
                  `}
                  aria-label={`Digit ${idx + 1} of 6`}
                />
              ))}
            </div>

            {/* Verifying indicator */}
            {otpState === "verifying" && (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="flex items-center justify-center gap-2 mb-3"
              >
                <FiLoader className="w-4 h-4 text-yellow-600 animate-spin" />
                <span className="text-sm text-yellow-700 font-medium">Verifying...</span>
              </motion.div>
            )}

            {/* Error message */}
            <AnimatePresence>
              {errorMessage && otpState !== "verifying" && (
                <motion.div
                  initial={{ opacity: 0, y: -5 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -5 }}
                  className="flex items-start gap-2 mb-3 px-3 py-2.5 bg-red-50 border border-red-200 rounded-lg"
                >
                  <FiAlertCircle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                  <div>
                    <p className="text-sm text-red-700">{errorMessage}</p>
                    {remainingAttempts !== null && remainingAttempts > 0 && (
                      <p className="text-xs text-red-500 mt-0.5">
                        {remainingAttempts} attempt{remainingAttempts !== 1 ? "s" : ""} remaining
                      </p>
                    )}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>

            {/* Timer and resend */}
            <div className="flex items-center justify-between text-xs">
              {/* Expiry timer */}
              {otpState === "sent" && expiresIn > 0 && (
                <span className="text-gray-500">
                  Code expires in{" "}
                  <span className="font-semibold text-gray-700">{formatTime(expiresIn)}</span>
                </span>
              )}
              {otpState === "expired" && (
                <span className="text-red-500 font-medium">Code expired</span>
              )}

              {/* Resend button */}
              <button
                type="button"
                onClick={handleSendOtp}
                disabled={countdown > 0 || otpState === "verifying"}
                className={`
                  inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                  text-xs font-semibold transition-all duration-200
                  focus:outline-none focus:ring-2 focus:ring-yellow-300
                  ${
                    countdown > 0 || otpState === "verifying"
                      ? "text-gray-400 cursor-not-allowed"
                      : "text-yellow-700 hover:bg-yellow-50 cursor-pointer"
                  }
                `}
              >
                <FiRefreshCw className="w-3.5 h-3.5" />
                {countdown > 0 ? `Resend in ${countdown}s` : "Resend Code"}
              </button>
            </div>
          </div>
        </motion.div>
      )}

      {/* ═══ ERROR STATE (send failure — allow retry) ═══ */}
      {otpState === "error" && (
        <motion.div
          key="error"
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={{ opacity: 0, height: 0 }}
          transition={{ duration: 0.3 }}
          className="mt-3"
        >
          <div className="flex items-start gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl mb-3">
            <FiAlertCircle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
            <p className="text-sm text-red-700">{errorMessage}</p>
          </div>
          <button
            type="button"
            onClick={handleSendOtp}
            disabled={countdown > 0 || !emailIsValid}
            className={`
              inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
              transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1
              ${
                countdown > 0 || !emailIsValid
                  ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                  : "bg-yellow-400 text-gray-900 hover:bg-yellow-500 focus:ring-yellow-400 shadow-sm hover:shadow-md cursor-pointer"
              }
            `}
          >
            <FiMail className="w-4 h-4" />
            {countdown > 0 ? `Retry in ${countdown}s` : "Retry Send OTP"}
          </button>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default OtpVerification;
