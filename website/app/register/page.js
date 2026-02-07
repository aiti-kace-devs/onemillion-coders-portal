"use client";

import React, { useState, useEffect, useCallback } from "react";
import { motion } from "framer-motion";
import { useRouter } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import {
  FiMapPin,
  FiChevronRight,
  FiUser,
  FiLoader,
  FiCheckCircle,
  FiAlertCircle,
  FiBookOpen,
  FiArrowLeft,
  FiClock,
} from "react-icons/fi";
import {
  getAllRegions,
  getRegionCenters,
  getCentreProgrammes,
  getRegistrationForm,
  submitRegistration,
  getConsentData,
} from "../../services/pages";
import Button from "../../components/Button";
import GhanaGradientText from "../../components/GhanaGradients/GhanaGradientText";
import { getCourseImage } from "../../utils/courseImages";

import parsePhoneNumberFromString from "libphonenumber-js";

import { useGoogleReCaptcha } from 'react-google-recaptcha-v3';



export default function RegisterPage() {
  const router = useRouter();
  const { executeRecaptcha } = useGoogleReCaptcha();

  // State management
  const [step, setStep] = useState(1); // 1: region, 2: center, 3: course, 4: form, 5: success
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Location and course selection state
  const [allRegions, setAllRegions] = useState(null);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [availableCenters, setAvailableCenters] = useState(null);
  const [selectedCentre, setSelectedCentre] = useState(null);
  const [availableCourses, setAvailableCourses] = useState(null);
  const [selectedCourse, setSelectedCourse] = useState(null);

  // Form state
  const [formSchema, setFormSchema] = useState(null);
  const [formData, setFormData] = useState({});
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [consentAccepted, setConsentAccepted] = useState(false);
  const [consentContent, setConsentContent] = useState("");

  // Fetch all regions on component mount
  useEffect(() => {
    fetchAllRegions();
  }, []);

  // Fetch consent content when user reaches form step
  useEffect(() => {
    if (step !== 4) return;
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

  // Fetch all regions
  const fetchAllRegions = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getAllRegions();
      setAllRegions(data);
    } catch (err) {
      setError("Failed to load regions. Please try again.");
      console.error("Error fetching regions:", err);
    } finally {
      setLoading(false);
    }
  };

  // Fetch centers when region is selected
  const fetchCenters = useCallback(async (regionId) => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRegionCenters(regionId);
      setAvailableCenters(data);
    } catch (err) {
      setError("Failed to load centers. Please try again.");
      console.error("Error fetching centers:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  // Fetch courses when center is selected
  const fetchCourses = useCallback(async (centreId) => {
    try {
      setLoading(true);
      setError(null);
      const data = await getCentreProgrammes(centreId);
      setAvailableCourses(data);
    } catch (err) {
      setError("Failed to load courses. Please try again.");
      console.error("Error fetching courses:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  // Fetch form schema when moving to form step
  const fetchFormSchema = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRegistrationForm();

      if (data && data.length > 0) {
        setFormSchema(data[0]);
        // Initialize form data (exclude course field since we handle it separately)
        const initialData = {};
        data[0].schema
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

  // Handle region selection
  const handleRegionSelect = (region) => {
    setSelectedRegion(region);
    setSelectedCentre(null);
    setSelectedCourse(null);
    setAvailableCenters(null);
    setAvailableCourses(null);
    fetchCenters(region.id);
    setStep(2);
  };

  // Handle centre selection
  const handleCentreSelect = (centre) => {
    setSelectedCentre(centre);
    setSelectedCourse(null);
    fetchCourses(centre.id);
    setStep(3);
  };

  // Handle course selection
  const handleCourseSelect = (course) => {
    setSelectedCourse(course);
    fetchFormSchema();
    setStep(4);
  };

  // Handle form field change
  const handleFieldChange = (fieldName, value) => {
    setFormData((prev) => ({
      ...prev,
      [fieldName]: value,
    }));

    // Clear error for this field
    if (formErrors[fieldName]) {
      setFormErrors((prev) => ({
        ...prev,
        [fieldName]: null,
      }));
    }
  };

  // Validate form
  const validateForm = () => {
    const errors = {};

    if (!formSchema?.schema) return errors;

    // Only validate fields that are actually displayed (exclude course field)
    formSchema.schema
      .filter((field) => field.field_name !== "course")
      .forEach((field) => {
        const value = formData[field.field_name];

        // Required validation
        if (
          field.validators?.required &&
          (!value || value.toString().trim() === "")
        ) {
          errors[field.field_name] = `${field.title} is required`;
          return;
        }

        // Email validation
        if (field.type === "email" && value) {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRegex.test(value)) {
            errors[field.field_name] = "Please enter a valid email address";
          }
        }

        // Phone validation
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
      });

    if (!consentAccepted) {
      errors.consent = "You must accept the terms and privacy policy to register.";
    }

    return errors;
  };
    

  // Handle form submission
  const handleSubmit = async (e) => {
    // Prevent default form submission and page reload
    if (e) {
      e.preventDefault();
    }

    const errors = validateForm();
    setFormErrors(errors);

    if (Object.keys(errors).length > 0) {
      return;
    }

    if (!executeRecaptcha) {
      setError("Failed to verify reCaptcha.");
      return;
    }

    const token = await executeRecaptcha('register_form'); 

    try {
      setSubmitting(true);

      // Prepare submission data - include course info since it's required
      const submissionData = {
        ...formData,
        course: selectedCourse.title, // Add course title since it's required by API
        programme_id: selectedCourse.id,
        region_id: selectedRegion.id,
        centre_id: selectedCentre.id,
        form_uuid: formSchema.uuid,
        recaptcha_token: token,
      };

      await submitRegistration(submissionData);
      setStep(5); // Success step
    } catch (err) {
      setError("Failed to submit registration. Please try again.");
      console.error("Error submitting registration:", err);
    } finally {
      setSubmitting(false);
    }
  };

  // Render form field based on type
  const renderFormField = (field) => {
    const value = formData[field.field_name] || "";
    const hasError = formErrors[field.field_name];

    const baseClasses = `w-full px-4 py-3 sm:py-3.5 border rounded-xl transition-all duration-200 text-base bg-white ${
      hasError
        ? "border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-200"
        : "border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200"
    } focus:outline-none hover:border-gray-400 disabled:bg-gray-50 disabled:cursor-not-allowed`;

    // Handle select fields
    if (field.type === "select" || field.type === "select_course") {
      return (
        <select
          value={value}
          onChange={(e) => handleFieldChange(field.field_name, e.target.value)}
          className={baseClasses}
        >
          <option value="">Select {field.title}</option>
          {field.options &&
            field.options.split(",").map((option, index) => (
              <option key={index} value={option.trim()}>
                {option.trim()}
              </option>
            ))}
        </select>
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
              // Parse the phone number with Ghana as default country
              const phoneNumber = parsePhoneNumberFromString(inputValue, "GH");
              
              if (phoneNumber && phoneNumber.isValid()) {
                // Store the international format in state
                handleFieldChange(field.field_name, phoneNumber.formatInternational());
              } else {
                // Store the raw input if it's not yet valid (user is still typing)
                handleFieldChange(field.field_name, inputValue);
              }
            } catch (error) {
              // Store the raw input if parsing fails
              handleFieldChange(field.field_name, inputValue);
            }
          }}
          onKeyPress={(e) => {
            // Allow numbers, spaces, +, -, parentheses, and control keys
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

  return (

    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div className="flex-1 min-w-0">
              <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 leading-tight">
                <GhanaGradientText variant="red-yellow-green">
                  Programme Registration
                </GhanaGradientText>
              </h1>
              <p className="text-gray-600 mt-1 text-sm sm:text-base leading-relaxed">
                Register for your preferred training programme
              </p>
            </div>
            <div className="flex-shrink-0">
              <Button
                onClick={() => router.push("/")}
                variant="ghost"
                icon={FiArrowLeft}
                iconPosition="left"
                className="w-full sm:w-auto min-h-[44px]"
              >
                Back to Home
              </Button>
            </div>
          </div>

          {/* Progress Steps */}
          <div className="mt-4 sm:mt-6 lg:mt-8">
            <div className="flex items-center justify-center space-x-1 sm:space-x-2 overflow-x-auto pb-2 px-2">
              {[1, 2, 3, 4].map((num) => (
                <React.Fragment key={num}>
                  <div
                    className={`w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 flex-shrink-0 ${
                      step >= num
                        ? "bg-yellow-400 text-gray-900 shadow-md"
                        : "bg-gray-200 text-gray-500"
                    }`}
                    role="progressbar"
                    aria-valuenow={step}
                    aria-valuemax={4}
                    aria-label={`Step ${num} of 4`}
                  >
                    {step > num ? (
                      <FiCheckCircle className="w-5 h-5 sm:w-6 sm:h-6" />
                    ) : (
                      num
                    )}
                  </div>
                  {num < 4 && (
                    <div
                      className={`w-12 sm:w-16 lg:w-20 h-1 rounded-full transition-all duration-300 flex-shrink-0 ${
                        step > num ? "bg-yellow-400" : "bg-gray-200"
                      }`}
                    ></div>
                  )}
                </React.Fragment>
              ))}
            </div>
            <div className="flex justify-center mt-3 sm:mt-4 px-4">
              <p className="text-xs sm:text-sm text-gray-600 text-center font-medium">
                {step === 1
                  ? "Select Region"
                  : step === 2
                  ? "Choose Training Center"
                  : step === 3
                  ? "Pick Your Course"
                  : step === 4
                  ? "Complete Registration"
                  : "Registration Complete!"}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 lg:py-12">
        {error && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-4 sm:mb-6 lg:mb-8 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3 shadow-sm"
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

        <motion.div
          className="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6 lg:p-8"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          {/* Step 1: Region Selection */}
          {step === 1 && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
            >
              <div className="flex items-center space-x-3 mb-6 sm:mb-8">
                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center flex-shrink-0">
                  <FiMapPin className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div className="min-w-0 flex-1">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                    Select Your Region
                  </h2>
                  <p className="text-gray-600 text-sm sm:text-base mt-1">
                    Choose the region where you&apos;d like to attend training
                  </p>
                </div>
              </div>

              {loading ? (
                <div className="flex flex-col items-center justify-center py-16 sm:py-20">
                  <div className="relative">
                    <FiLoader className="w-10 h-10 sm:w-12 sm:h-12 animate-spin text-yellow-600" />
                    <div className="absolute inset-0 w-10 h-10 sm:w-12 sm:h-12 border-2 border-yellow-200 rounded-full animate-pulse"></div>
                  </div>
                  <p className="text-gray-600 mt-4 text-sm sm:text-base font-medium">
                    Loading regions...
                  </p>
                </div>
              ) : allRegions && allRegions.length > 0 ? (
                <div className="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2">
                  {allRegions.map((region) => (
                    <motion.button
                      key={region.id}
                      onClick={() => handleRegionSelect(region)}
                      className="p-4 sm:p-6 rounded-xl border-2 border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:bg-yellow-50 hover:shadow-md group min-h-[80px] sm:min-h-[90px] flex items-center"
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      aria-label={`Select ${region.title} region`}
                    >
                      <div className="flex items-center justify-between w-full">
                        <div className="min-w-0 flex-1">
                          <h3 className="text-base sm:text-lg font-semibold text-gray-900 group-hover:text-yellow-700 truncate">
                            {region.title}
                          </h3>
                          <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Training centers available
                          </p>
                        </div>
                        <FiChevronRight className="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-yellow-600 flex-shrink-0 ml-3 transition-transform group-hover:translate-x-1" />
                      </div>
                    </motion.button>
                  ))}
                </div>
              ) : (
                <div className="text-center py-16 sm:py-20">
                  <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiMapPin className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                  </div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    No regions available
                  </h3>
                  <p className="text-gray-600 mb-4">
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
              )}
            </motion.div>
          )}

          {/* Step 2: Centre Selection */}
          {step === 2 && selectedRegion && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
            >
              <div className="flex items-center space-x-3 mb-6 sm:mb-8">
                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                  <FiMapPin className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div className="min-w-0 flex-1">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                    Choose Training Center
                  </h2>
                  <p className="text-gray-600 text-sm sm:text-base mt-1">
                    Select a training center in {selectedRegion.title}
                  </p>
                </div>
              </div>

              {loading ? (
                <div className="flex flex-col items-center justify-center py-16 sm:py-20">
                  <div className="relative">
                    <FiLoader className="w-10 h-10 sm:w-12 sm:h-12 animate-spin text-yellow-600" />
                    <div className="absolute inset-0 w-10 h-10 sm:w-12 sm:h-12 border-2 border-yellow-200 rounded-full animate-pulse"></div>
                  </div>
                  <p className="text-gray-600 mt-4 text-sm sm:text-base font-medium">
                    Loading training centers...
                  </p>
                </div>
              ) : availableCenters?.centres &&
                availableCenters.centres.length > 0 ? (
                <div className="space-y-3 sm:space-y-4">
                  {availableCenters.centres.map((centre) => (
                    <motion.button
                      key={centre.id}
                      onClick={() => handleCentreSelect(centre)}
                      className="w-full p-4 sm:p-6 rounded-xl border-2 border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:bg-yellow-50 hover:shadow-md group min-h-[80px] sm:min-h-[90px] flex items-center"
                      whileHover={{ scale: 1.01 }}
                      whileTap={{ scale: 0.99 }}
                      aria-label={`Select ${centre.title} training center`}
                    >
                      <div className="flex items-center justify-between w-full">
                        <div className="min-w-0 flex-1">
                          <h3 className="text-base sm:text-lg font-semibold text-gray-900 group-hover:text-yellow-700 truncate">
                            {centre.title}
                          </h3>
                        </div>
                        <FiChevronRight className="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-yellow-600 flex-shrink-0 ml-3 transition-transform group-hover:translate-x-1" />
                      </div>
                    </motion.button>
                  ))}
                </div>
              ) : (
                <div className="text-center py-16 sm:py-20">
                  <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiMapPin className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                  </div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    No training centers available
                  </h3>
                  <p className="text-gray-600 mb-4">
                    No training centers found in {selectedRegion.title}.
                  </p>
                  <Button
                    onClick={() => setStep(1)}
                    variant="outline"
                    className="min-h-[44px]"
                  >
                    Choose Different Region
                  </Button>
                </div>
              )}

              <div className="mt-6 sm:mt-8">
                <Button
                  onClick={() => setStep(1)}
                  variant="outline"
                  icon={FiArrowLeft}
                  iconPosition="left"
                  className="w-full sm:w-auto min-h-[48px] px-6"
                >
                  Back to Regions
                </Button>
              </div>
            </motion.div>
          )}

          {/* Step 3: Course Selection */}
          {step === 3 && selectedCentre && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
            >
              <div className="flex items-center space-x-3 mb-6 sm:mb-8">
                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                  <FiBookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div className="min-w-0 flex-1">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                    Select Your Course
                  </h2>
                  <p className="text-gray-600 text-sm sm:text-base mt-1">
                    Available courses at {selectedCentre.title}
                  </p>
                </div>
              </div>

              {loading ? (
                <div className="flex flex-col items-center justify-center py-16 sm:py-20">
                  <div className="relative">
                    <FiLoader className="w-10 h-10 sm:w-12 sm:h-12 animate-spin text-yellow-600" />
                    <div className="absolute inset-0 w-10 h-10 sm:w-12 sm:h-12 border-2 border-yellow-200 rounded-full animate-pulse"></div>
                  </div>
                  <p className="text-gray-600 mt-4 text-sm sm:text-base font-medium">
                    Loading available courses...
                  </p>
                </div>
              ) : availableCourses?.programmes ? (
                <div className="space-y-4 sm:space-y-6">
                  {availableCourses.programmes.map((course) => (
                    <motion.button
                      key={course.id}
                      onClick={() => handleCourseSelect(course)}
                      className="w-full p-4 sm:p-6 rounded-xl border-2 border-gray-200 text-left transition-all duration-200 hover:border-yellow-400 hover:bg-yellow-50 hover:shadow-md group"
                      whileHover={{ scale: 1.01 }}
                      whileTap={{ scale: 0.99 }}
                      aria-label={`Select ${course.title} course`}
                    >
                      <div className="flex flex-col sm:flex-row gap-4">
                        <div className="relative w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden flex-shrink-0 mx-auto sm:mx-0 border border-gray-200">
                          <Image
                            // TEMPORARY: Commented out API image, using static image for consistency
                            // src={
                            //   course.image ||
                            //   "/images/hero/Certified-Data-Protection-Manager.jpg"
                            // }
                            src={getCourseImage(course.id)}
                            alt={course.title}
                            fill
                            className="object-cover"
                          />
                        </div>
                        <div className="flex-1 min-w-0 text-center sm:text-left">
                          <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between h-full">
                            <div className="min-w-0 flex-1">
                              <h3 className="text-lg sm:text-xl font-semibold text-gray-900 group-hover:text-yellow-700 mb-2">
                                {course.title}
                              </h3>
                              {course.sub_title && (
                                <p className="text-sm text-gray-600 mb-3">
                                  {course.sub_title}
                                </p>
                              )}
                              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-sm text-gray-500">
                                <div className="flex items-center space-x-1 bg-gray-100 px-2 py-1 rounded-full">
                                  <FiClock className="w-4 h-4" />
                                  <span>{course.duration}</span>
                                </div>
                                {course.category?.title && (
                                  <span className="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                    {course.category.title}
                                  </span>
                                )}
                              </div>
                            </div>
                            <FiChevronRight className="w-6 h-6 text-gray-400 group-hover:text-yellow-600 flex-shrink-0 mx-auto sm:mx-0 mt-3 sm:mt-0 sm:ml-4 transition-transform group-hover:translate-x-1" />
                          </div>
                        </div>
                      </div>
                    </motion.button>
                  ))}
                </div>
              ) : (
                <div className="text-center py-16 sm:py-20">
                  <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FiBookOpen className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                  </div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    No courses available
                  </h3>
                  <p className="text-gray-600 mb-4">
                    No courses found at {selectedCentre.title}.
                  </p>
                  <Button
                    onClick={() => setStep(2)}
                    variant="outline"
                    className="min-h-[44px]"
                  >
                    Choose Different Center
                  </Button>
                </div>
              )}

              <div className="mt-6 sm:mt-8">
                <Button
                  onClick={() => setStep(2)}
                  variant="outline"
                  icon={FiArrowLeft}
                  iconPosition="left"
                  className="w-full sm:w-auto min-h-[48px] px-6"
                >
                  Back to Centers
                </Button>
              </div>
            </motion.div>
          )}

          {/* Step 4: Registration Form */}
          {step === 4 && selectedCourse && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
            >
              <div className="flex items-center space-x-3 mb-6 sm:mb-8">
                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                  <FiUser className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <div className="min-w-0 flex-1">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                    Complete Registration
                  </h2>
                  <p className="text-gray-600 text-sm sm:text-base mt-1">
                    Fill in your details to register for {selectedCourse.title}
                  </p>
                </div>
              </div>

              {/* Selection Summary */}
              <div className="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 sm:p-6 mb-6 sm:mb-8 border border-gray-200">
                <h3 className="font-semibold text-gray-900 mb-3 sm:mb-4 text-base flex items-center">
                  <FiCheckCircle className="w-5 h-5 text-green-600 mr-2" />
                  Registration Summary
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                  <div className="bg-white rounded-lg p-3 border border-gray-200">
                    <p className="text-gray-600 text-xs font-medium uppercase tracking-wide">
                      Region
                    </p>
                    <p className="font-semibold text-gray-900 mt-1 truncate">
                      {selectedRegion.title}
                    </p>
                  </div>
                  <div className="bg-white rounded-lg p-3 border border-gray-200">
                    <p className="text-gray-600 text-xs font-medium uppercase tracking-wide">
                      Training Center
                    </p>
                    <p className="font-semibold text-gray-900 mt-1 truncate">
                      {selectedCentre.title}
                    </p>
                  </div>
                  <div className="bg-white rounded-lg p-3 border border-gray-200">
                    <p className="text-gray-600 text-xs font-medium uppercase tracking-wide">
                      Course
                    </p>
                    <p className="font-semibold text-gray-900 mt-1 truncate">
                      {selectedCourse.title}
                    </p>
                  </div>
                </div>
              </div>

              {loading ? (
                <div className="flex flex-col items-center justify-center py-16 sm:py-20">
                  <div className="relative">
                    <FiLoader className="w-10 h-10 sm:w-12 sm:h-12 animate-spin text-yellow-600" />
                    <div className="absolute inset-0 w-10 h-10 sm:w-12 sm:h-12 border-2 border-yellow-200 rounded-full animate-pulse"></div>
                  </div>
                  <p className="text-gray-600 mt-4 text-sm sm:text-base font-medium">
                    Loading registration form...
                  </p>
                </div>
              ) : formSchema ? (
                <form
                  className="space-y-5 sm:space-y-6"
                  onSubmit={handleSubmit}
                >
                  {formSchema.schema
                    .filter((field) => field.field_name !== "course") // Hide course field
                    .map((field) => (
                      <div key={field.field_name} className="space-y-2">
                        <label className="block text-sm font-semibold text-gray-900">
                          {field.title}
                          {field.validators?.required && (
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
                        {field.description && !formErrors[field.field_name] && (
                          <p className="text-sm text-gray-500 leading-relaxed">
                            {field.description}
                          </p>
                        )}
                      </div>
                    ))}

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
                    <label className="flex items-start gap-3 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={consentAccepted}
                        onChange={(e) => setConsentAccepted(e.target.checked)}
                        className="mt-1 w-4 h-4 rounded border-gray-300 text-yellow-500 focus:ring-yellow-500"
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

                  <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-6 sm:pt-8 border-t border-gray-200">
                    <Button
                      onClick={() => setStep(3)}
                      variant="outline"
                      className="w-full sm:flex-1 order-2 sm:order-1 min-h-[48px]"
                      type="button"
                    >
                      Back to Courses
                    </Button>
                    <Button
                      type="submit"
                      disabled={submitting}
                      icon={submitting ? FiLoader : FiCheckCircle}
                      className="w-full sm:flex-1 order-1 sm:order-2 min-h-[48px] font-semibold"
                    >
                      {submitting ? "Submitting..." : "Submit Registration"}
                    </Button>
                  </div>
                </form>
              ) : (
                <div className="text-center py-12">
                  <p className="text-gray-600">
                    Failed to load registration form.
                  </p>
                </div>
              )}
            </motion.div>
          )}

          {/* Step 5: Success */}
          {step === 5 && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
              className="text-center py-8 sm:py-12"
            >
              <motion.div
                className="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-lg"
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
              >
                <FiCheckCircle className="w-10 h-10 sm:w-12 sm:h-12 text-green-600" />
              </motion.div>
              <motion.h2
                className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 sm:mb-4 px-4"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
              >
                Registration Successful!
              </motion.h2>
              <motion.p
                className="text-gray-600 mb-6 sm:mb-8 leading-relaxed max-w-2xl mx-auto text-sm sm:text-base px-4"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
              >
                {formSchema?.message_after_registration ||
                  "Thank you for registering! Further instructions will be sent to you via email/SMS."}
              </motion.p>
              <motion.div
                className="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 px-4"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
              >
                <Button
                  onClick={() => router.push("/")}
                  variant="outline"
                  className="w-full sm:w-auto min-h-[48px] px-6"
                >
                  Back to Home
                </Button>
                <Button
                  onClick={() => router.push("/programmes")}
                  variant="primary"
                  className="w-full sm:w-auto min-h-[48px] px-6 font-semibold"
                >
                  Browse More Courses
                </Button>
              </motion.div>
            </motion.div>
          )}
        </motion.div>
      </div>
    </div> 
  );
}
