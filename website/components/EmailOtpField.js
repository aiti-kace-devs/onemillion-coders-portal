"use client";

import React, { useState, useEffect, useRef } from "react";
import { motion } from "framer-motion";
import {
  FiMail,
  FiCheckCircle,
  FiLoader,
  FiAlertCircle,
} from "react-icons/fi";
import { sendEmailOtp, verifyEmailOtp } from "../services/emailOtp";

const EmailOtpField = ({
  value,
  onChange,
  onVerifiedChange,
  isVerified,
  hasError,
  placeholder,
  baseInputClasses,
}) => {
  // 'idle' | 'sending' | 'sent' | 'verifying' | 'verified'
  const [otpStatus, setOtpStatus] = useState("idle");
  const [otpCode, setOtpCode] = useState("");
  const [otpError, setOtpError] = useState("");
  const [cooldownSeconds, setCooldownSeconds] = useState(0);
  const verifiedEmailRef = useRef("");

  const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  // Reset OTP state when email changes after verification
  useEffect(() => {
    if (isVerified && value !== verifiedEmailRef.current) {
      onVerifiedChange(false);
      setOtpStatus("idle");
      setOtpCode("");
      setOtpError("");
    }
  }, [value, isVerified, onVerifiedChange]);

  // Cooldown timer for resend
  useEffect(() => {
    if (cooldownSeconds <= 0) return;
    const timer = setTimeout(() => setCooldownSeconds((s) => s - 1), 1000);
    return () => clearTimeout(timer);
  }, [cooldownSeconds]);

  const handleSendOtp = async () => {
    if (!isValidEmail(value)) return;
    setOtpStatus("sending");
    setOtpError("");
    try {
      await sendEmailOtp(value);
      setOtpStatus("sent");
      setCooldownSeconds(60);
    } catch {
      setOtpStatus("idle");
      setOtpError("Failed to send verification code. Please try again.");
    }
  };

  const handleVerifyOtp = async () => {
    if (otpCode.length !== 6) return;
    setOtpStatus("verifying");
    setOtpError("");
    try {
      const result = await verifyEmailOtp(value, otpCode);
      if (result.success) {
        setOtpStatus("verified");
        verifiedEmailRef.current = value;
        onVerifiedChange(true);
      } else {
        setOtpStatus("sent");
        setOtpError(result.message || "Invalid code. Please try again.");
      }
    } catch {
      setOtpStatus("sent");
      setOtpError("Verification failed. Please try again.");
    }
  };

  const handleResendOtp = async () => {
    if (cooldownSeconds > 0) return;
    await handleSendOtp();
  };

  const handleOtpChange = (e) => {
    const val = e.target.value.replace(/\D/g, "").slice(0, 6);
    setOtpCode(val);
    setOtpError("");
  };

  return (
    <div className="space-y-3">
      {/* Email input */}
      <div className="relative">
        <input
          type="email"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className={`${baseInputClasses} ${isVerified ? "pr-10" : ""}`}
          placeholder={placeholder}
          autoComplete="email"
          disabled={otpStatus === "verifying" || otpStatus === "sending"}
        />
        {isVerified && (
          <div className="absolute right-3 top-1/2 -translate-y-1/2">
            <FiCheckCircle className="w-5 h-5 text-green-500" />
          </div>
        )}
      </div>

      {/* Verified badge */}
      {isVerified && (
        <motion.p
          initial={{ opacity: 0, y: -5 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-sm text-green-600 flex items-center font-medium"
        >
          <FiCheckCircle className="w-4 h-4 mr-1" />
          Email verified
        </motion.p>
      )}

      {/* "Verify Email" button */}
      {!isVerified && isValidEmail(value) && otpStatus === "idle" && (
        <motion.button
          type="button"
          onClick={handleSendOtp}
          initial={{ opacity: 0, y: -5 }}
          animate={{ opacity: 1, y: 0 }}
          className="px-4 py-2 text-sm font-semibold rounded-lg bg-yellow-400 text-gray-900 hover:bg-yellow-500 transition-colors duration-200 flex items-center space-x-2"
        >
          <FiMail className="w-4 h-4" />
          <span>Verify Email</span>
        </motion.button>
      )}

      {/* Sending state */}
      {otpStatus === "sending" && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="flex items-center space-x-2 text-sm text-gray-600"
        >
          <FiLoader className="w-4 h-4 animate-spin text-yellow-600" />
          <span>Sending verification code...</span>
        </motion.div>
      )}

      {/* OTP input section */}
      {(otpStatus === "sent" || otpStatus === "verifying") && (
        <motion.div
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-3 p-4 bg-gray-50 rounded-xl border border-gray-200"
        >
          <p className="text-sm text-gray-700">
            Enter the 6-digit code sent to <strong>{value}</strong>
          </p>
          <div className="flex items-center space-x-3">
            <input
              type="text"
              value={otpCode}
              onChange={handleOtpChange}
              placeholder="000000"
              maxLength={6}
              inputMode="numeric"
              className="w-32 px-4 py-3 border border-gray-300 rounded-xl text-center text-lg font-mono tracking-widest focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 focus:outline-none"
              disabled={otpStatus === "verifying"}
              autoFocus
            />
            <button
              type="button"
              onClick={handleVerifyOtp}
              disabled={otpCode.length !== 6 || otpStatus === "verifying"}
              className="px-4 py-3 text-sm font-semibold rounded-xl bg-yellow-400 text-gray-900 hover:bg-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 flex items-center space-x-2"
            >
              {otpStatus === "verifying" ? (
                <>
                  <FiLoader className="w-4 h-4 animate-spin" />
                  <span>Verifying...</span>
                </>
              ) : (
                <span>Verify Code</span>
              )}
            </button>
          </div>

          {otpError && (
            <motion.p
              initial={{ opacity: 0, y: -5 }}
              animate={{ opacity: 1, y: 0 }}
              className="text-sm text-red-600 flex items-center"
            >
              <FiAlertCircle className="w-4 h-4 mr-1" />
              {otpError}
            </motion.p>
          )}

          <div className="text-sm">
            {cooldownSeconds > 0 ? (
              <span className="text-gray-500">
                Resend code in {cooldownSeconds}s
              </span>
            ) : (
              <button
                type="button"
                onClick={handleResendOtp}
                className="text-yellow-600 hover:text-yellow-700 font-medium underline"
              >
                Resend code
              </button>
            )}
          </div>
        </motion.div>
      )}

      {/* Error when send failed */}
      {otpStatus === "idle" && otpError && (
        <motion.p
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="text-sm text-red-600 flex items-center"
        >
          <FiAlertCircle className="w-4 h-4 mr-1" />
          {otpError}
        </motion.p>
      )}
    </div>
  );
};

export default EmailOtpField;
