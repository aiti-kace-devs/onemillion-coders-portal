"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  FiAlertCircle,
  FiArrowRight,
  FiCheckCircle,
  FiEye,
  FiEyeOff,
  FiKey,
  FiLoader,
  FiLock,
  FiShield,
} from "react-icons/fi";
import {
  activateProtocolAccount,
  initializeProtocolActivation,
} from "../../../services/api";

const GHANA_CARD_REGEX = /^GHA-\d{9}-\d$/;
const MAX_ID_TRIALS = 3;

function formatGhanaCardInput(rawValue) {
  const digits = String(rawValue || "").replace(/\D/g, "").slice(0, 10);
  const firstNine = digits.slice(0, 9);
  const lastDigit = digits.slice(9, 10);

  if (!firstNine) return "";
  if (!lastDigit) return `GHA-${firstNine}`;
  return `GHA-${firstNine}-${lastDigit}`;
}

function UnknownState({ title, message }) {
  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,#fff6d6_0%,#fff9ed_36%,#f4f0e6_100%)] px-4 py-10">
      <div className="mx-auto flex min-h-[80vh] max-w-3xl items-center justify-center">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          className="w-full max-w-xl overflow-hidden rounded-[32px] border border-black/5 bg-white/95 shadow-[0_24px_80px_rgba(32,26,14,0.12)]"
        >
          <div className="border-b border-black/5 bg-[linear-gradient(135deg,#201a0e_0%,#3a2f19_100%)] px-8 py-7 text-white">
            <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10">
              <FiAlertCircle className="h-7 w-7" />
            </div>
            <h1 className="text-2xl font-black tracking-tight">{title}</h1>
          </div>
          <div className="px-8 py-8">
            <p className="text-base leading-7 text-[#5f5a50]">{message}</p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link
                href="/"
                className="inline-flex items-center rounded-full bg-[#f9a825] px-5 py-3 text-sm font-bold text-[#18140b] transition hover:bg-[#f57f17]"
              >
                Return Home
              </Link>
              <span className="inline-flex items-center rounded-full border border-[#e6dfd0] px-5 py-3 text-sm text-[#6a6458]">
                Contact the admin for a new invitation if needed
              </span>
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
}

export default function ActivationClient({ token }) {
  const [status, setStatus] = useState("loading");
  const [sessionToken, setSessionToken] = useState("");
  const [participant, setParticipant] = useState(null);
  const [nationalId, setNationalId] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
  const [attemptsRemaining, setAttemptsRemaining] = useState(MAX_ID_TRIALS);
  const [submitting, setSubmitting] = useState(false);
  const [formErrors, setFormErrors] = useState({});
  const [notice, setNotice] = useState(null);

  const lastCountedMismatchRef = useRef("");
  const noticeTimerRef = useRef(null);

  useEffect(() => {
    if (noticeTimerRef.current) {
      window.clearTimeout(noticeTimerRef.current);
    }

    if (!notice) {
      return undefined;
    }

    noticeTimerRef.current = window.setTimeout(() => {
      setNotice(null);
    }, notice.timeout || 4200);

    return () => {
      if (noticeTimerRef.current) {
        window.clearTimeout(noticeTimerRef.current);
      }
    };
  }, [notice]);

  useEffect(() => {
    let cancelled = false;

    async function initialize() {
      try {
        const response = await initializeProtocolActivation(token);
        if (cancelled) {
          return;
        }

        setSessionToken(response.session_token || "");
        setParticipant(response.participant || null);
        setAttemptsRemaining(response.attempts?.remaining ?? MAX_ID_TRIALS);
        setStatus(response.status === "ready" ? "ready" : "unknown");
      } catch (error) {
        if (cancelled) {
          return;
        }

        const payload = error.response?.data || {};
        setStatus(payload.status || "unknown");
        setNotice({
          type: "error",
          message: payload.message || "This activation link is not available.",
          timeout: 5200,
        });
      }
    }

    initialize();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const expectedNationalId = participant?.ghcard || "";
  const passwordsMatch = password !== "" && password === passwordConfirmation;
  const passwordStrong =
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(password);
  const nationalIdMatches = expectedNationalId !== "" && nationalId === expectedNationalId;
  const nationalIdLocked = attemptsRemaining <= 0;

  function pushNotice(type, message, timeout = 4200) {
    setNotice({ type, message, timeout });
  }

  function handleNationalIdChange(value) {
    const formatted = formatGhanaCardInput(value);
    setNationalId(formatted);

    if (formErrors.national_id) {
      setFormErrors((current) => ({ ...current, national_id: null }));
    }

    if (
      GHANA_CARD_REGEX.test(formatted) &&
      expectedNationalId &&
      formatted !== expectedNationalId &&
      lastCountedMismatchRef.current !== formatted &&
      attemptsRemaining > 0
    ) {
      const nextRemaining = Math.max(0, attemptsRemaining - 1);
      lastCountedMismatchRef.current = formatted;
      setAttemptsRemaining(nextRemaining);

      if (nextRemaining === 0) {
        pushNotice(
          "error",
          "All National ID trials have been used for this one-time activation session.",
          5200,
        );
      } else {
        pushNotice(
          "warning",
          `National ID does not match your saved ID info. ${nextRemaining} trial(s) remaining.`,
        );
      }
    }

    if (formatted === expectedNationalId) {
      lastCountedMismatchRef.current = "";
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (submitting || nationalIdLocked) {
      return;
    }

    const nextErrors = {};
    if (!nationalIdMatches) {
      nextErrors.national_id = "National ID must exactly match the Ghana Card number on this invitation.";
    }
    if (!passwordStrong) {
      nextErrors.password =
        "Use at least 8 characters with uppercase, lowercase, number, and symbol.";
    }
    if (!passwordsMatch) {
      nextErrors.password_confirmation = "Confirm password must match password.";
    }

    setFormErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) {
      return;
    }

    setSubmitting(true);

    try {
      const response = await activateProtocolAccount({
        token,
        session_token: sessionToken,
        national_id: nationalId,
        password,
        password_confirmation: passwordConfirmation,
      });

      setStatus("success");
      pushNotice(
        "success",
        response.message ||
          "Your account is active and the standard welcome email has been sent.",
        5200,
      );
    } catch (error) {
      const payload = error.response?.data || {};
      if (payload.errors) {
        setFormErrors({
          national_id: payload.errors.national_id?.[0] || null,
          password: payload.errors.password?.[0] || null,
          password_confirmation:
            payload.errors.password_confirmation?.[0] || null,
        });
      }

      if (payload.attempts?.remaining !== undefined) {
        setAttemptsRemaining(payload.attempts.remaining);
      }

      if (payload.status && payload.status !== "validation_error") {
        setStatus(payload.status);
      }

      pushNotice(
        "error",
        payload.message || "Activation could not be completed. Please contact the administrator.",
        5200,
      );
    } finally {
      setSubmitting(false);
    }
  }

  if (status === "loading") {
    return (
      <div className="min-h-screen bg-[radial-gradient(circle_at_top,#fff6d6_0%,#fff9ed_36%,#f4f0e6_100%)] px-4 py-10">
        <div className="mx-auto flex min-h-[80vh] max-w-3xl items-center justify-center">
          <div className="rounded-[28px] border border-black/5 bg-white/95 px-8 py-10 text-center shadow-[0_24px_80px_rgba(32,26,14,0.12)]">
            <FiLoader className="mx-auto h-8 w-8 animate-spin text-[#f9a825]" />
            <p className="mt-4 text-sm font-medium text-[#5f5a50]">
              Preparing your activation session...
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (status === "unknown") {
    return (
      <UnknownState
        title="Unknown Invitation"
        message="This activation link is not recognized. Please check the exact link from your email or request a fresh invitation from the administrator."
      />
    );
  }

  if (status === "used") {
    return (
      <UnknownState
        title="Link Already Used"
        message="This activation link has already been used to complete account setup and cannot be reused. Please contact the administrator for a new invitation if needed."
      />
    );
  }

  if (status === "expired") {
    return (
      <UnknownState
        title="Session Expired"
        message="The activation page was opened, but the secure session is no longer valid. Please contact the administrator for a fresh invitation."
      />
    );
  }

  if (status === "locked") {
    return (
      <UnknownState
        title="Activation Locked"
        message="This activation session has been locked after repeated failed National ID attempts. Please contact the administrator for a new invitation."
      />
    );
  }

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_top,#fff8e1_0%,#fffdf7_42%,#f6faf6_100%)] px-4 py-6 sm:px-6 lg:px-8">
      <div className="mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl items-center justify-center">
        <div className="grid w-full max-w-5xl overflow-hidden rounded-[36px] border border-black/5 bg-white shadow-[0_36px_120px_rgba(32,26,14,0.14)] lg:grid-cols-[1.05fr_0.95fr]">
          <div className="relative overflow-hidden bg-[linear-gradient(160deg,#151515_0%,#242424_42%,#2e7d32_160%)] px-7 py-8 text-white sm:px-10 sm:py-10">
            <div className="absolute left-0 right-0 top-0 h-1.5 bg-[linear-gradient(90deg,#c62828_0%,#f9a825_50%,#2e7d32_100%)]" />
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,168,37,0.22),transparent_38%)]" />
            <div className="relative">
              <div className="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/8 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-white/85">
                <FiShield className="h-4 w-4" />
                Account Activation
              </div>
              <h1 className="mt-6 max-w-md text-3xl font-black leading-tight tracking-tight sm:text-4xl">
                Complete your account setup in one secure step.
              </h1>
              <p className="mt-4 max-w-md text-sm leading-7 text-white/75 sm:text-base">
                This invitation was prepared for
                {" "}
                <span className="font-bold text-white">
                  {participant?.full_name || participant?.first_name || "you"}
                </span>
                . Match your National ID exactly, choose your password, and activate the account.
              </p>

              <div className="mt-8 space-y-4">
                <div className="rounded-3xl border border-white/10 bg-white/8 p-5">
                  <div className="text-xs uppercase tracking-[0.2em] text-white/55">
                    Secure Invitation
                  </div>
                  <div className="mt-2 flex items-center gap-3 text-lg font-bold text-[#ffd54f]">
                    <FiKey className="h-5 w-5 text-[#ffd54f]" />
                    Signed link verified
                  </div>
                  <p className="mt-3 text-sm leading-6 text-white/70">
                    This one-time activation page is securely linked to
                    {" "}
                    <span className="font-semibold text-white">
                      {participant?.email || "your pending profile"}
                    </span>
                    .
                  </p>
                </div>

                <div className="rounded-3xl border border-white/10 bg-white/8 p-5">
                  <div className="text-xs uppercase tracking-[0.2em] text-white/55">
                    National ID Trials Remaining
                  </div>
                  <div className="mt-3 flex items-center gap-2">
                    {Array.from({ length: MAX_ID_TRIALS }).map((_, index) => {
                      const active = index < attemptsRemaining;
                      return (
                        <span
                          key={index}
                          className={`h-2.5 flex-1 rounded-full ${
                            active ? "bg-[#f9a825]" : "bg-white/12"
                          }`}
                        />
                      );
                    })}
                  </div>
                  <p className="mt-3 text-sm text-white/70">
                    {attemptsRemaining} of {MAX_ID_TRIALS} trial(s) still available.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
            <AnimatePresence>
              {notice && (
                <motion.div
                  initial={{ opacity: 0, y: -10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  className={`mb-5 flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm ${
                    notice.type === "success"
                      ? "border-green-200 bg-green-50 text-green-800"
                      : notice.type === "warning"
                        ? "border-amber-200 bg-amber-50 text-amber-800"
                        : "border-red-200 bg-red-50 text-red-800"
                  }`}
                >
                  {notice.type === "success" ? (
                    <FiCheckCircle className="mt-0.5 h-4 w-4 shrink-0" />
                  ) : (
                    <FiAlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                  )}
                  <span>{notice.message}</span>
                </motion.div>
              )}
            </AnimatePresence>

            {status === "success" ? (
              <motion.div
                initial={{ opacity: 0, y: 18 }}
                animate={{ opacity: 1, y: 0 }}
                className="rounded-[30px] border border-[#dfe8d8] bg-[linear-gradient(180deg,#f8fff4_0%,#ffffff_100%)] p-7 shadow-[0_22px_60px_rgba(55,105,55,0.08)]"
              >
                <div className="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-[#eaf8e4] text-[#2f7d32]">
                  <FiCheckCircle className="h-8 w-8" />
                </div>
                <h2 className="mt-5 text-2xl font-black tracking-tight text-[#1b241c]">
                  Account Activated
                </h2>
                <p className="mt-3 text-sm leading-7 text-[#56705a] sm:text-base">
                  Your details have been securely moved into the main student system,
                  and the regular welcome email has been sent to
                  {" "}
                  <strong>{participant?.email}</strong>.
                </p>
                <div className="mt-8 flex flex-wrap gap-3">
                  <Link
                    href="/"
                    className="inline-flex items-center rounded-full bg-[#f9a825] px-5 py-3 text-sm font-bold text-[#18140b] transition hover:bg-[#f57f17]"
                  >
                    Finish
                  </Link>
                </div>
              </motion.div>
            ) : (
              <>
                <div className="mb-6">
                  <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#9f8857]">
                    Final Step
                  </p>
                  <h2 className="mt-2 text-3xl font-black tracking-tight text-[#17130e]">
                    Activate your learner account
                  </h2>
                  <p className="mt-3 max-w-xl text-sm leading-7 text-[#6a6458] sm:text-base">
                    Enter the exact National ID from the link, create a strong password,
                    and confirm it before activation becomes available.
                  </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label className="mb-2 block text-sm font-semibold text-[#1e1a13]">
                      National ID
                    </label>
                    <input
                      type="text"
                      value={nationalId}
                      onChange={(event) => handleNationalIdChange(event.target.value)}
                      disabled={nationalIdLocked}
                      placeholder="GHA-123456789-0"
                      className={`w-full rounded-[20px] border bg-[#fcfbf7] px-4 py-3.5 font-mono text-base tracking-[0.08em] text-[#201b15] outline-none transition ${
                        formErrors.national_id
                          ? "border-red-300 focus:border-red-400"
                          : nationalIdMatches
                            ? "border-green-300 focus:border-green-400"
                            : "border-[#dfd7c9] focus:border-[#d8a200]"
                      } ${nationalIdLocked ? "cursor-not-allowed opacity-60" : ""}`}
                    />
                    <div className="mt-2 flex flex-wrap items-center gap-3 text-xs">
                      <span
                        className={`inline-flex items-center rounded-full px-3 py-1 font-semibold ${
                          nationalIdMatches
                            ? "bg-green-50 text-green-700"
                            : "bg-[#f8f4ea] text-[#8a7d5f]"
                        }`}
                      >
                        {nationalIdMatches
                          ? "National ID matches the invitation"
                          : "National ID must exactly match the Ghana Card on file"}
                      </span>
                      <span className="text-[#8a7d5f]">
                        Remaining trials: {attemptsRemaining}
                      </span>
                    </div>
                    {formErrors.national_id && (
                      <p className="mt-2 text-sm text-red-600">{formErrors.national_id}</p>
                    )}
                  </div>

                  <div>
                    <label className="mb-2 block text-sm font-semibold text-[#1e1a13]">
                      Password
                    </label>
                    <div className="relative">
                      <input
                        type={showPassword ? "text" : "password"}
                        value={password}
                        onChange={(event) => {
                          setPassword(event.target.value);
                          if (formErrors.password) {
                            setFormErrors((current) => ({ ...current, password: null }));
                          }
                        }}
                        placeholder="Create a strong password"
                        className={`w-full rounded-[20px] border bg-[#fcfbf7] px-4 py-3.5 pr-12 text-base text-[#201b15] outline-none transition ${
                          formErrors.password
                            ? "border-red-300 focus:border-red-400"
                            : "border-[#dfd7c9] focus:border-[#d8a200]"
                        }`}
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword((current) => !current)}
                        className="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center text-[#7a7469]"
                        aria-label={showPassword ? "Hide password" : "Show password"}
                      >
                        {showPassword ? <FiEyeOff className="h-5 w-5" /> : <FiEye className="h-5 w-5" />}
                      </button>
                    </div>
                    <div className="mt-2 text-xs text-[#8a7d5f]">
                      Use at least 8 characters with uppercase, lowercase, number, and symbol.
                    </div>
                    {formErrors.password && (
                      <p className="mt-2 text-sm text-red-600">{formErrors.password}</p>
                    )}
                  </div>

                  <div>
                    <label className="mb-2 block text-sm font-semibold text-[#1e1a13]">
                      Confirm Password
                    </label>
                    <div className="relative">
                      <input
                        type={showPasswordConfirmation ? "text" : "password"}
                        value={passwordConfirmation}
                        onChange={(event) => {
                          setPasswordConfirmation(event.target.value);
                          if (formErrors.password_confirmation) {
                            setFormErrors((current) => ({
                              ...current,
                              password_confirmation: null,
                            }));
                          }
                        }}
                        placeholder="Re-enter the same password"
                        className={`w-full rounded-[20px] border bg-[#fcfbf7] px-4 py-3.5 pr-12 text-base text-[#201b15] outline-none transition ${
                          formErrors.password_confirmation
                            ? "border-red-300 focus:border-red-400"
                            : passwordsMatch && passwordConfirmation
                              ? "border-green-300 focus:border-green-400"
                              : "border-[#dfd7c9] focus:border-[#d8a200]"
                        }`}
                      />
                      <button
                        type="button"
                        onClick={() =>
                          setShowPasswordConfirmation((current) => !current)
                        }
                        className="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center text-[#7a7469]"
                        aria-label={
                          showPasswordConfirmation
                            ? "Hide confirm password"
                            : "Show confirm password"
                        }
                      >
                        {showPasswordConfirmation ? (
                          <FiEyeOff className="h-5 w-5" />
                        ) : (
                          <FiEye className="h-5 w-5" />
                        )}
                      </button>
                    </div>
                    {passwordConfirmation && !passwordsMatch && (
                      <p className="mt-2 text-sm text-red-600">
                        Confirm password must match password.
                      </p>
                    )}
                    {formErrors.password_confirmation && (
                      <p className="mt-2 text-sm text-red-600">
                        {formErrors.password_confirmation}
                      </p>
                    )}
                  </div>

                  <div className="rounded-[24px] border border-[#ebe4d7] bg-[#fcfaf5] p-4">
                    <div className="flex items-start gap-3">
                      <div className="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#efe4bb] text-[#7e5b00]">
                        <FiLock className="h-5 w-5" />
                      </div>
                      <div>
                        <h3 className="text-sm font-bold text-[#241d13]">
                          Activation checklist
                        </h3>
                        <ul className="mt-3 space-y-2 text-sm text-[#6a6458]">
                          <li className={nationalIdMatches ? "text-green-700" : ""}>
                            National ID matches the Ghana Card on the invitation
                          </li>
                          <li className={passwordStrong ? "text-green-700" : ""}>
                            Password meets the strength rule
                          </li>
                          <li className={passwordsMatch ? "text-green-700" : ""}>
                            Password and confirm password match
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={
                      submitting ||
                      nationalIdLocked ||
                      !nationalIdMatches ||
                      !passwordStrong ||
                      !passwordsMatch
                    }
                    className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#f9a825] px-6 py-4 text-sm font-black uppercase tracking-[0.18em] text-[#18140b] transition hover:bg-[#f57f17] disabled:cursor-not-allowed disabled:bg-[#e7dec7] disabled:text-[#817868]"
                  >
                    {submitting ? (
                      <>
                        <FiLoader className="h-4 w-4 animate-spin" />
                        Activating...
                      </>
                    ) : (
                      <>
                        Activate Account
                        <FiArrowRight className="h-4 w-4" />
                      </>
                    )}
                  </button>
                </form>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
