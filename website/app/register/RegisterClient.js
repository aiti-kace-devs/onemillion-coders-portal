"use client";

import React, { useState, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import {
  FiUser,
  FiLoader,
  FiCheckCircle,
  FiAlertCircle,
  FiArrowLeft,
  FiArrowRight,
  FiClock,
  FiUpload,
  FiChevronDown,
} from "react-icons/fi";
import {
  getRegistrationForm,
  submitRegistration,
  getConsentData,
  checkEmailAvailability,
} from "../../services/pages";
import Button from "../../components/Button";
import GhanaGradientText from "../../components/GhanaGradients/GhanaGradientText";
import OtpVerification from "../../components/OtpVerification";

import parsePhoneNumberFromString from "libphonenumber-js";

import { useGoogleReCaptcha } from 'react-google-recaptcha-v3';

export default function RegisterClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { executeRecaptcha } = useGoogleReCaptcha();

  // Read pre-selected IDs from query params (set by /courses page)
  const regionId = searchParams.get("region_id");
  const centreId = searchParams.get("centre_id");
  const programmeId = searchParams.get("programme_id");

  // State management
  const [step, setStep] = useState("form"); // "form" | "success"
  const [currentGroupIndex, setCurrentGroupIndex] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Form state
  const [formSchema, setFormSchema] = useState(null);
  const [groupedSchema, setGroupedSchema] = useState([]);
  const [formData, setFormData] = useState({});
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [confirmPassword, setConfirmPassword] = useState("");
  const [consentAccepted, setConsentAccepted] = useState(false);
  const [consentContent, setConsentContent] = useState("");

  // OTP verification state
  const [otpVerified, setOtpVerified] = useState(false);
  const [emailFieldName, setEmailFieldName] = useState(null);
  const [phoneFieldName, setPhoneFieldName] = useState(null);

  // Real-time email availability state
  const [emailAvailability, setEmailAvailability] = useState({ status: null, message: "" });
  const emailCheckTimerRef = React.useRef(null);

  // Fetch form schema on mount
  useEffect(() => {
    fetchFormSchema();
  }, []);

  // Detect email and phone fields from the form schema (case-insensitive)
  useEffect(() => {
    if (formSchema?.schema) {
      const eField = formSchema.schema.find(
        (f) => f.type?.toLowerCase() === "email"
      );
      const pField = formSchema.schema.find(
        (f) =>
          f.type?.toLowerCase() === "phonenumber" ||
          f.type?.toLowerCase() === "phone"
      );
      setEmailFieldName(eField?.field_name || null);
      setPhoneFieldName(pField?.field_name || null);
    }
  }, [formSchema]);

  // Fetch consent content when user reaches form step
  useEffect(() => {
    if (step !== "form") return;
    let cancelled = false;
    getConsentData()
      .then((res) => {
        if (cancelled) return;
        const raw = res?.data ?? res;
        setConsentContent(raw?.content ?? "");
      })
      .catch(() => {
        if (!cancelled) setConsentContent("");
      });
    return () => { cancelled = true; };
  }, [step]);

  // Fetch form schema
  const fetchFormSchema = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRegistrationForm();

      if (data && data.length > 0) {
        const form = data[0];
        setFormSchema(form);

        // Use grouped_schema if available, otherwise fall back to single group
        const groups = form.grouped_schema && form.grouped_schema.length > 0
          ? form.grouped_schema.filter(
              (g) => g.fields.some((f) => f.field_name !== "course")
            )
          : [{ title: "Registration", fields: form.schema.filter((f) => f.field_name !== "course") }];

        setGroupedSchema(groups);

        const initialData = {};
        form.schema
          .filter((field) => field.field_name !== "course")
          .forEach((field) => {
            initialData[field.field_name] = "";
          });
        setFormData(initialData);
      }
    } catch (err) {
      setError("Failed to load registration form. Please try again.");
      console.error("Error fetching form schema:", err);
    } finally {
      setLoading(false);
    }
  };

  // Debounced real-time email availability check
  const checkEmailAvailabilityDebounced = useCallback(
    (emailValue) => {
      if (emailCheckTimerRef.current) {
        clearTimeout(emailCheckTimerRef.current);
        emailCheckTimerRef.current = null;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailValue || !emailRegex.test(emailValue.trim())) {
        setEmailAvailability({ status: null, message: "" });
        return;
      }

      setEmailAvailability({ status: "checking", message: "Checking email availability..." });

      emailCheckTimerRef.current = setTimeout(async () => {
        try {
          const result = await checkEmailAvailability(emailValue.trim());
          if (result?.available) {
            setEmailAvailability({ status: "available", message: "Email is available." });
          } else if (result?.reason === "registered") {
            setEmailAvailability({
              status: "registered",
              message: result?.message || "This email is already registered.",
            });
          } else if (result?.reason === "used") {
            setEmailAvailability({
              status: "used",
              message: result?.message || "This email has already been used for registration.",
            });
          } else if (result?.reason === "otp_active") {
            setEmailAvailability({
              status: "otp_active",
              message: "A verification code was already sent to this email.",
            });
          } else {
            setEmailAvailability({
              status: "error",
              message: result?.message || "Email is not available.",
            });
          }
        } catch (err) {
          setEmailAvailability({ status: null, message: "" });
        }
      }, 600);
    },
    []
  );

  // Cleanup email check timer on unmount
  React.useEffect(() => {
    return () => {
      if (emailCheckTimerRef.current) {
        clearTimeout(emailCheckTimerRef.current);
      }
    };
  }, []);

  // Handle form field change
  const handleFieldChange = (fieldName, value) => {
    setFormData((prev) => ({
      ...prev,
      [fieldName]: value,
    }));

    if (fieldName === emailFieldName && otpVerified) {
      setOtpVerified(false);
    }

    if (fieldName === emailFieldName) {
      setEmailAvailability({ status: null, message: "" });
      checkEmailAvailabilityDebounced(value);
    }

    // Clear field-level error and top-level error banner when user makes changes
    if (formErrors[fieldName]) {
      setFormErrors((prev) => ({
        ...prev,
        [fieldName]: null,
      }));
    }
    if (error) {
      setError(null);
    }
  };

  // Validate fields for a specific group
  const validateGroup = (groupIndex) => {
    const errors = {};
    const group = groupedSchema[groupIndex];
    if (!group) return errors;

    group.fields
      .filter((field) => field.field_name !== "course")
      .forEach((field) => {
        const value = formData[field.field_name];

        if (
          (field.required === "1" || field.validators?.required) &&
          (!value || value.toString().trim() === "")
        ) {
          errors[field.field_name] = `${field.title} is required`;
          return;
        }

        if (field.type === "email" && value) {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(value)) {
            errors[field.field_name] = "Please enter a valid email address";
          } else if (emailAvailability.status === "registered") {
            errors[field.field_name] = "This email is already registered. Please use a different email.";
          } else if (emailAvailability.status === "used") {
            errors[field.field_name] = "This email has already been used for registration. Please use a different email.";
          }
        }

        if (field.type === "phonenumber" && value) {
          try {
            const phoneNumber = parsePhoneNumberFromString(value, "GH");
            if (!phoneNumber || !phoneNumber.isValid()) {
              errors[field.field_name] = "Please enter a valid Ghana phone number";
            }
          } catch (error) {
            errors[field.field_name] = "Please enter a valid Ghana phone number";
          }
        }

        // Password validation
        if (field.type === "password" && value) {
          if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,64}$/.test(value)) {
            errors[field.field_name] = "Password must be at least 6 characters with an uppercase letter, a lowercase letter, and a number.";
          } else if (value !== confirmPassword) {
            errors[field.field_name] = "Passwords do not match.";
          }
        }

        if (field.type === "file" && value instanceof File && field.options) {
          const allowedExtensions = field.options.split(",").map((ext) => ext.trim().toLowerCase());
          const fileName = value.name || "";
          const fileExtension = fileName.split(".").pop()?.toLowerCase();
          if (!fileExtension || !allowedExtensions.includes(fileExtension)) {
            errors[field.field_name] = `Only ${allowedExtensions.map((e) => e.toUpperCase()).join(", ")} files are allowed`;
          }
        }
      });

    return errors;
  };

  // Validate entire form (all groups)
  const validateForm = () => {
    let errors = {};
    groupedSchema.forEach((_, index) => {
      Object.assign(errors, validateGroup(index));
    });

    if (!consentAccepted) {
      errors.consent = "You must accept the terms and privacy policy to register.";
    }

    if (emailFieldName && !otpVerified) {
      errors.otp = "Please verify your email address with the OTP code before submitting.";
    }

    return errors;
  };

  // Handle next step
  const handleNextStep = () => {
    const errors = validateGroup(currentGroupIndex);
    setFormErrors(errors);

    if (Object.keys(errors).length > 0) {
      const firstErrorField = Object.keys(errors)[0];
      setTimeout(() => {
        const el = document.getElementById(`field-${firstErrorField}`);
        if (el) {
          el.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }, 100);
      return;
    }

    if (currentGroupIndex < groupedSchema.length - 1) {
      setCurrentGroupIndex(currentGroupIndex + 1);
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  // Handle previous step
  const handlePrevStep = () => {
    if (currentGroupIndex > 0) {
      setCurrentGroupIndex(currentGroupIndex - 1);
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  // Check if current group is the last one
  const isLastGroup = currentGroupIndex === groupedSchema.length - 1;

  // Handle form submission
  const handleSubmit = async (e) => {
    if (e) {
      e.preventDefault();
    }

    const errors = validateForm();
    setFormErrors(errors);

    if (Object.keys(errors).length > 0) {
      // Find the first group with an error and navigate to it
      for (let i = 0; i < groupedSchema.length; i++) {
        const groupErrors = validateGroup(i);
        if (Object.keys(groupErrors).length > 0) {
          setCurrentGroupIndex(i);
          break;
        }
      }
      return;
    }

    try {
      setSubmitting(true);

      const payload = new FormData();

      // Append all form fields
      Object.entries(formData).forEach(([key, value]) => {
        if (value instanceof File) {
          payload.append(key, value);
        } else if (value !== "" && value !== null && value !== undefined) {
          payload.append(key, value);
        }
      });

      payload.append("course", 27);
      payload.append("form_uuid", formSchema.uuid);

      if (programmeId) payload.append("programme_id", programmeId);
      if (regionId) payload.append("region_id", regionId);
      if (centreId) payload.append("centre_id", centreId);

      await submitRegistration(payload);
      setStep("success");
    } catch (err) {
      const responseData = err?.response?.data;

      if (responseData?.errors) {
        // Map server validation errors to form field errors
        const serverErrors = {};
        Object.entries(responseData.errors).forEach(([field, messages]) => {
          serverErrors[field] = Array.isArray(messages) ? messages[0] : messages;
        });
        setFormErrors(serverErrors);

        // Navigate to the step containing the first errored field and scroll to it
        const firstErrorField = Object.keys(serverErrors)[0];
        for (let i = 0; i < groupedSchema.length; i++) {
          const hasErrorField = groupedSchema[i].fields.some(
            (f) => serverErrors[f.field_name]
          );
          if (hasErrorField) {
            setCurrentGroupIndex(i);
            // Wait for render then scroll to the errored field
            setTimeout(() => {
              const el = document.getElementById(`field-${firstErrorField}`);
              if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
              }
            }, 300);
            break;
          }
        }

        setError(responseData.message || "Please fix the errors below.");
      } else {
        setError(
          responseData?.message || "Failed to submit registration. Please try again."
        );
      }
      console.error("Error submitting registration:", err);
    } finally {
      setSubmitting(false);
    }
  };

  // Render form field based on type
  const renderFormField = (field) => {
    const value = formData[field.field_name] || "";
    const hasError = formErrors[field.field_name];

    const baseClasses = `w-full px-4 py-3 sm:py-3.5 border rounded-xl transition-all duration-200 text-sm sm:text-base bg-white placeholder:text-gray-400 ${
      hasError
        ? "border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-200"
        : "border-gray-200 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-100"
    } focus:outline-none hover:border-gray-300 disabled:bg-gray-50 disabled:cursor-not-allowed`;

    // Handle radio fields
    if (field.type === "radio") {
      const options = field.options ? field.options.split(",") : [];
      return (
        <div className="flex flex-wrap gap-3">
          {options.map((option, index) => {
            const trimmed = option.trim();
            const isSelected = value === trimmed;
            return (
              <button
                key={index}
                type="button"
                onClick={() => handleFieldChange(field.field_name, trimmed)}
                className={`px-4 py-2.5 rounded-xl text-sm font-medium border transition-all duration-200 ${
                  isSelected
                    ? "border-yellow-400 bg-yellow-50 text-gray-900"
                    : "border-gray-200 bg-white text-gray-600 hover:border-gray-300"
                }`}
              >
                {trimmed}
              </button>
            );
          })}
        </div>
      );
    }

    // Handle select fields
    if (field.type === "select" || field.type === "select_course") {
      return (
        <div className="relative">
          <select
            value={value}
            onChange={(e) => handleFieldChange(field.field_name, e.target.value)}
            className={`${baseClasses} appearance-none pr-10`}
          >
            <option value="">Select {field.title}</option>
            {field.options &&
              field.options.split(",").map((option, index) => (
                <option key={index} value={option.trim()}>
                  {option.trim()}
                </option>
              ))}
          </select>
          <FiChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
        </div>
      );
    }

    // Handle file upload
    if (field.type === "file" || field.type === "image") {
      return (
        <div
          className={`relative border-2 border-dashed rounded-xl p-6 text-center transition-all duration-200 cursor-pointer ${
            hasError
              ? "border-red-300 bg-red-50"
              : value
              ? "border-green-300 bg-green-50"
              : "border-gray-200 hover:border-yellow-400 hover:bg-yellow-50"
          }`}
          onDragOver={(e) => {
            e.preventDefault();
            e.currentTarget.classList.add("border-yellow-400", "bg-yellow-50");
          }}
          onDragLeave={(e) => {
            e.preventDefault();
            e.currentTarget.classList.remove("border-yellow-400", "bg-yellow-50");
          }}
          onDrop={(e) => {
            e.preventDefault();
            e.currentTarget.classList.remove("border-yellow-400", "bg-yellow-50");
            const file = e.dataTransfer.files[0];
            if (file) {
              handleFieldChange(field.field_name, file);
            }
          }}
          onClick={() => document.getElementById(`file-${field.field_name}`).click()}
        >
          <input
            id={`file-${field.field_name}`}
            type="file"
            className="hidden"
            accept={
              field.type === "image"
                ? "image/*"
                : field.options
                ? field.options.split(",").map((ext) => `.${ext.trim().toLowerCase()}`).join(",")
                : undefined
            }
            onChange={(e) => {
              const file = e.target.files[0];
              if (file) {
                handleFieldChange(field.field_name, file);
              }
            }}
          />
          <FiUpload className="w-8 h-8 text-gray-400 mx-auto mb-2" />
          {value ? (
            <p className="text-sm text-green-700 font-medium">{value.name || "File selected"}</p>
          ) : (
            <>
              <p className="text-sm text-gray-600 font-medium">
                Drag & drop or click to upload
              </p>
              <p className="text-xs text-gray-400 mt-1">
                {field.type === "image"
                  ? "PNG, JPG, GIF up to 5MB"
                  : field.options
                  ? `${field.options.split(",").map((e) => e.trim().toUpperCase()).join(", ")} up to 10MB`
                  : "PDF, DOC up to 10MB"}
              </p>
            </>
          )}
        </div>
      );
    }

    // Map field types to input types
    const getInputType = (fieldType) => {
      switch (fieldType) {
        case "phonenumber":
          return "tel";
        case "email":
          return "email";
        case "number":
          return "number";
        case "password":
          return "password";
        default:
          return "text";
      }
    };

    const inputType = getInputType(field.type);
    const placeholder =
      field.description || `Enter your ${field.title.toLowerCase()}`;

    // Special handling for phone numbers
    if (field.type === "phonenumber") {
      return (
        <input
          type={inputType}
          value={value}
          onChange={(e) => {
            const inputValue = e.target.value;

            try {
              const phoneNumber = parsePhoneNumberFromString(inputValue, "GH");

              if (phoneNumber && phoneNumber.isValid()) {
                handleFieldChange(field.field_name, phoneNumber.formatInternational());
              } else {
                handleFieldChange(field.field_name, inputValue);
              }
            } catch (error) {
              handleFieldChange(field.field_name, inputValue);
            }
          }}
          onKeyPress={(e) => {
            const allowedChars = /[0-9+\-\s()]/;
            if (
              !allowedChars.test(e.key) &&
              !['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)
            ) {
              e.preventDefault();
            }
          }}
          className={baseClasses}
          placeholder="e.g., 024 123 4567 or +233 24 123 4567"
          autoComplete="tel"
          inputMode="tel"
        />
      );
    }

    // Password field with validation checklist
    if (field.type === "password") {
      const checks = [
        { label: "At least 6 characters", met: value.length >= 6 },
        { label: "Contains at least one uppercase letter", met: /[A-Z]/.test(value) },
        { label: "Contains at least one lowercase letter", met: /[a-z]/.test(value) },
        { label: "Contains a number", met: /\d/.test(value) },
      ];

      return (
        <div>
          <input
            type="password"
            value={value}
            onChange={(e) => handleFieldChange(field.field_name, e.target.value)}
            className={baseClasses}
            placeholder={placeholder}
            autoComplete="new-password"
          />
          <div className="mt-3">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Confirm Password <span className="text-red-400">*</span>
            </label>
            <input
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className={`w-full px-4 py-3 sm:py-3.5 border rounded-xl transition-all duration-200 text-sm sm:text-base bg-white placeholder:text-gray-400 ${
                confirmPassword && confirmPassword !== value && !value.startsWith(confirmPassword)
                  ? "border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-200"
                  : confirmPassword && confirmPassword === value
                  ? "border-green-300 focus:border-green-500 focus:ring-2 focus:ring-green-200"
                  : "border-gray-200 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-100"
              } focus:outline-none hover:border-gray-300`}
              placeholder="Re-enter your password"
              autoComplete="new-password"
            />
            {confirmPassword && confirmPassword !== value && !value.startsWith(confirmPassword) && (
              <p className="mt-1 text-sm text-red-600 flex items-center">
                <FiAlertCircle className="w-4 h-4 mr-1 flex-shrink-0" />
                Passwords do not match.
              </p>
            )}
          </div>
          {value.length > 0 && (
            <ul className="mt-2 grid grid-cols-2 gap-x-4 gap-y-1">
              {checks.map((check, i) => (
                <li key={i} className="flex items-center gap-2 text-xs sm:text-sm">
                  <span
                    className={`inline-flex items-center justify-center w-4 h-4 shrink-0 rounded border transition-colors duration-200 ${
                      check.met
                        ? "bg-green-500 border-green-500 text-white"
                        : "border-gray-300 bg-white"
                    }`}
                  >
                    {check.met && (
                      <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    )}
                  </span>
                  <span className={check.met ? "text-green-600" : "text-gray-500"}>
                    {check.label}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>
      );
    }

    // Standard input field
    return (
      <input
        type={inputType}
        value={value}
        onChange={(e) => handleFieldChange(field.field_name, e.target.value)}
        className={baseClasses}
        placeholder={placeholder}
        autoComplete={field.type === "email" ? "email" : "off"}
      />
    );
  };

  // Get current group
  const currentGroup = groupedSchema[currentGroupIndex];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-[76px] sm:top-[84px] z-10">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
          <div className="flex items-center justify-between gap-3">
            <div className="flex-1 min-w-0">
              <h1 className="text-lg sm:text-xl font-bold text-gray-900 leading-tight">
                <GhanaGradientText variant="red-yellow-green">
                  Programme Registration
                </GhanaGradientText>
              </h1>
            </div>
            <button
              onClick={() => router.push("/")}
              className="flex items-center gap-1.5 text-xs sm:text-sm text-gray-500 hover:text-gray-700 transition-colors py-1.5 px-2.5 sm:px-3 rounded-lg hover:bg-gray-50 flex-shrink-0"
            >
              <FiArrowLeft className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span className="hidden sm:inline">Back to Home</span>
            </button>
          </div>

          {/* Stepper */}
          {step === "form" && groupedSchema.length > 1 && (
            <div className="pb-4 pt-4 sm:pt-3 border-t border-gray-100 mt-3">
              <div className="flex items-center max-w-lg mx-auto">
                {groupedSchema.map((group, index) => (
                  <React.Fragment key={index}>
                    <button
                      onClick={() => {
                        if (index < currentGroupIndex) {
                          setCurrentGroupIndex(index);
                        }
                      }}
                      disabled={index > currentGroupIndex}
                      className="flex items-center gap-1 sm:gap-2 group flex-shrink-0"
                    >
                      <div
                        className={`w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold transition-all duration-300 ${
                          index < currentGroupIndex
                            ? "bg-green-500 text-white cursor-pointer group-hover:bg-green-600"
                            : index === currentGroupIndex
                            ? "bg-yellow-400 text-gray-900 ring-2 sm:ring-4 ring-yellow-100"
                            : "bg-gray-200 text-gray-400"
                        }`}
                      >
                        {index < currentGroupIndex ? (
                          <FiCheckCircle className="w-3 h-3 sm:w-4 sm:h-4" />
                        ) : (
                          index + 1
                        )}
                      </div>
                      <span
                        className={`hidden sm:inline text-xs font-medium transition-colors ${
                          index <= currentGroupIndex ? "text-gray-700" : "text-gray-400"
                        }`}
                      >
                        {group.title}
                      </span>
                    </button>
                    {index < groupedSchema.length - 1 && (
                      <div
                        className={`flex-1 h-0.5 rounded-full mx-1.5 sm:mx-3 transition-all duration-500 ${
                          index < currentGroupIndex ? "bg-green-400" : "bg-gray-200"
                        }`}
                      />
                    )}
                  </React.Fragment>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-3xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
        {error && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3"
          >
            <FiAlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
            <div className="flex-1 min-w-0">
              <p className="text-red-700 text-sm sm:text-base font-medium">
                {error}
              </p>
              <button
                onClick={() => setError(null)}
                className="text-red-600 text-sm underline mt-1 hover:text-red-800 transition-colors"
              >
                Dismiss
              </button>
            </div>
          </motion.div>
        )}

        {/* Registration Form */}
        {step === "form" && (
          <div>
            {loading ? (
              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                {/* Skeleton loader */}
                <div className="animate-pulse space-y-6">
                  <div className="space-y-2">
                    <div className="h-5 bg-gray-200 rounded w-1/3" />
                    <div className="h-3 bg-gray-100 rounded w-2/3" />
                  </div>
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="space-y-2">
                      <div className="h-4 bg-gray-200 rounded w-1/4" />
                      <div className="h-12 bg-gray-100 rounded-xl" />
                    </div>
                  ))}
                </div>
              </div>
            ) : formSchema && currentGroup ? (
              <AnimatePresence mode="wait" initial={false}>
                <motion.div
                  key={currentGroupIndex}
                  initial={{ opacity: 0, y: 8 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.25, ease: "easeOut" }}
                >
                  <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    {/* Group header */}
                    <div className="px-4 sm:px-6 pt-5 sm:pt-6 pb-4">
                      <h2 className="text-lg sm:text-xl font-bold text-gray-900">
                        {currentGroup.title}
                      </h2>
                      <p className="text-gray-500 text-xs sm:text-sm mt-0.5">
                        Step {currentGroupIndex + 1} of {groupedSchema.length}
                      </p>
                    </div>

                    {/* Fields */}
                    <form
                      className="px-4 sm:px-6 pb-6 space-y-5"
                      onSubmit={(e) => {
                        e.preventDefault();
                        if (isLastGroup) {
                          handleSubmit();
                        } else {
                          handleNextStep();
                        }
                      }}
                    >
                      {currentGroup.fields
                        .filter((field) => field.field_name !== "course")
                        .map((field) => (
                          <div key={field.field_name} id={`field-${field.field_name}`} className="space-y-1.5">
                            <label className="block text-sm font-medium text-gray-700">
                              {field.title}
                              {(field.required === "1" || field.validators?.required) && (
                                <span
                                  className="text-red-500 ml-1"
                                  aria-label="required"
                                >
                                  *
                                </span>
                              )}
                            </label>
                            {renderFormField(field)}
                            {formErrors[field.field_name] && (
                              <motion.p
                                initial={{ opacity: 0, y: -5 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="text-sm text-red-600 flex items-center"
                              >
                                <FiAlertCircle className="w-4 h-4 mr-1 flex-shrink-0" />
                                {formErrors[field.field_name]}
                              </motion.p>
                            )}
                            {field.type === "password" && !formErrors[field.field_name] && formData[field.field_name] && /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,64}$/.test(formData[field.field_name]) && (
                              <motion.div
                                initial={{ opacity: 0, y: -5 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="grid grid-cols-2 gap-x-4 gap-y-1 mt-1"
                              >
                                <p className="text-sm text-green-600 flex items-center">
                                  <FiCheckCircle className="w-4 h-4 mr-1 flex-shrink-0" />
                                  Password is good to go!
                                </p>
                                {confirmPassword === formData[field.field_name] && (
                                  <p className="text-sm text-green-600 flex items-center">
                                    <FiCheckCircle className="w-4 h-4 mr-1 flex-shrink-0" />
                                    Passwords match!
                                  </p>
                                )}
                              </motion.div>
                            )}
                            {field.description && !formErrors[field.field_name] && (
                              <p className="text-xs text-gray-400 leading-relaxed">
                                {field.description}
                              </p>
                            )}

                            {/* Real-time email availability indicator */}
                            {field.type?.toLowerCase() === "email" && emailAvailability.status && (
                              <motion.div
                                initial={{ opacity: 0, y: -5 }}
                                animate={{ opacity: 1, y: 0 }}
                                className={`flex items-center gap-2 text-sm mt-1 ${
                                  emailAvailability.status === "checking"
                                    ? "text-gray-500"
                                    : emailAvailability.status === "available"
                                    ? "text-green-600"
                                    : emailAvailability.status === "otp_active"
                                    ? "text-amber-600"
                                    : "text-red-600"
                                }`}
                              >
                                {emailAvailability.status === "checking" && (
                                  <FiLoader className="w-3.5 h-3.5 animate-spin flex-shrink-0" />
                                )}
                                {emailAvailability.status === "available" && (
                                  <FiCheckCircle className="w-3.5 h-3.5 flex-shrink-0" />
                                )}
                                {emailAvailability.status === "otp_active" && (
                                  <FiClock className="w-3.5 h-3.5 flex-shrink-0" />
                                )}
                                {(emailAvailability.status === "registered" ||
                                  emailAvailability.status === "used" ||
                                  emailAvailability.status === "error") && (
                                  <FiAlertCircle className="w-3.5 h-3.5 flex-shrink-0" />
                                )}
                                <span>{emailAvailability.message}</span>
                              </motion.div>
                            )}

                            {/* OTP Verification */}
                            {field.type?.toLowerCase() === "email" &&
                              emailAvailability.status !== "registered" &&
                              emailAvailability.status !== "used" && (
                              <OtpVerification
                                email={formData[field.field_name] || ""}
                                phone={phoneFieldName ? (formData[phoneFieldName] || "") : ""}
                                formUuid={formSchema.uuid}
                                onVerified={(verified) => {
                                  setOtpVerified(verified);
                                  if (verified && formErrors.otp) {
                                    setFormErrors((prev) => ({ ...prev, otp: null }));
                                  }
                                }}
                                emailStatus={emailAvailability.status}
                              />
                            )}
                          </div>
                        ))}

                      {/* Consent + OTP errors on last step */}
                      {isLastGroup && (
                        <>
                          {/* OTP verification error */}
                          {formErrors.otp && (
                            <motion.div
                              initial={{ opacity: 0, y: -5 }}
                              animate={{ opacity: 1, y: 0 }}
                              className="flex items-center gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl"
                            >
                              <FiAlertCircle className="w-4 h-4 text-amber-600 flex-shrink-0" />
                              <p className="text-sm text-amber-700 font-medium">{formErrors.otp}</p>
                            </motion.div>
                          )}

                          {/* Consent block */}
                          <div className="space-y-3 pt-4 border-t border-gray-200">
                            <p className="text-sm text-gray-700 leading-relaxed">
                              I have read the {" "}
                              <Link
                                href="/terms-and-privacy"
                                className="text-yellow-600 hover:text-yellow-700 font-medium underline underline-offset-2"
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                Terms & Privacy Policy
                              </Link>
                              {consentContent ? " " : ""}
                              {consentContent ? (
                                <span dangerouslySetInnerHTML={{ __html: consentContent }} />
                              ) : null}
                            </p>
                            <label className="flex items-start gap-3 cursor-pointer group">
                              <input
                                type="checkbox"
                                checked={consentAccepted}
                                onChange={(e) => {
                                setConsentAccepted(e.target.checked);
                                if (formErrors.consent) {
                                  setFormErrors((prev) => ({ ...prev, consent: null }));
                                }
                                if (error) {
                                  setError(null);
                                }
                              }}
                                className="mt-0.5 w-[18px] h-[18px] rounded border-gray-300 text-yellow-500 focus:ring-yellow-500 group-hover:border-yellow-400 transition-colors"
                              />
                              <span className="text-sm text-gray-700">
                                I accept the terms and privacy policy
                              </span>
                            </label>
                            {formErrors.consent && (
                              <motion.p
                                initial={{ opacity: 0, y: -5 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="text-sm text-red-600 flex items-center"
                              >
                                <FiAlertCircle className="w-4 h-4 mr-1 flex-shrink-0" />
                                {formErrors.consent}
                              </motion.p>
                            )}
                          </div>
                        </>
                      )}

                      {/* Navigation buttons */}
                      <div className="flex gap-3 pt-4">
                        {currentGroupIndex > 0 && (
                          <button
                            type="button"
                            onClick={handlePrevStep}
                            className="flex items-center justify-center gap-2 px-5 py-3 border border-gray-200 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-50 transition-colors"
                          >
                            <FiArrowLeft className="w-4 h-4" />
                            Back
                          </button>
                        )}
                        <button
                          type="submit"
                          disabled={submitting}
                          className="flex-1 flex items-center justify-center gap-2 py-3 font-semibold text-sm rounded-xl transition-colors bg-yellow-400 hover:bg-yellow-500 text-gray-900 disabled:opacity-50"
                        >
                          {isLastGroup ? (
                            submitting ? (
                              <>
                                <FiLoader className="w-4 h-4 animate-spin" />
                                Submitting...
                              </>
                            ) : (
                              <>
                                Submit Registration
                                <FiCheckCircle className="w-4 h-4" />
                              </>
                            )
                          ) : (
                            <>
                              Continue
                              <FiArrowRight className="w-4 h-4" />
                            </>
                          )}
                        </button>
                      </div>
                    </form>
                  </div>
                </motion.div>
              </AnimatePresence>
            ) : (
              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <p className="text-gray-600">
                  Failed to load registration form.
                </p>
              </div>
            )}
          </div>
        )}

        {/* Success */}
        {step === "success" && (
          <motion.div
            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
          >
            <div className="text-center py-4 sm:py-8">
              <motion.div
                className="w-16 h-16 sm:w-20 sm:h-20 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-5"
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
              >
                <FiCheckCircle className="w-8 h-8 sm:w-10 sm:h-10 text-green-600" />
              </motion.div>
              <motion.h2
                className="text-xl sm:text-2xl font-bold text-gray-900 mb-2"
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
              >
                Registration Successful!
              </motion.h2>
              <motion.p
                className="text-gray-500 mb-6 sm:mb-8 text-sm sm:text-base max-w-md mx-auto"
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
              >
                {formSchema?.message_after_registration ||
                  "Thank you for registering! Further instructions will be sent to you via email/SMS."}
              </motion.p>
              <motion.div
                className="flex flex-col sm:flex-row justify-center gap-3"
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
              >
                <button
                  onClick={() => router.push("/")}
                  className="px-5 py-3 border border-gray-200 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-50 transition-colors"
                >
                  Back to Home
                </button>
                <button
                  onClick={() => router.push("/programmes")}
                  className="px-5 py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm rounded-xl transition-colors"
                >
                  Browse More Courses
                </button>
              </motion.div>
            </div>
          </motion.div>
        )}
      </div>
    </div>
  );
}
