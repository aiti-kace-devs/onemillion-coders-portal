"use client";

import React, { useState, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import {
  FiX,
  FiMapPin,
  FiChevronRight,
  FiUser,
  FiLoader,
  FiCheckCircle,
  FiAlertCircle,
  FiClock,
} from "react-icons/fi";
import {
  getProgrammeLocations,
  getRegistrationForm,
  submitRegistration,
} from "../services/pages";
import Button from "./Button";
import GhanaGradientText from "./GhanaGradients/GhanaGradientText";

const RegistrationDialog = ({ isOpen, onClose, programme }) => {
  // State management
  const [step, setStep] = useState(1); // 1: location, 2: form, 3: success
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Location selection state
  const [locations, setLocations] = useState(null);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [selectedCentre, setCentre] = useState(null);

  // Form state
  const [formSchema, setFormSchema] = useState(null);
  const [formData, setFormData] = useState({});
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Fetch programme locations
  const fetchLocations = useCallback(async () => {
    if (!programme?.id) return;

    try {
      setLoading(true);
      setError(null);
      const data = await getProgrammeLocations(programme.id);
      setLocations(data);
    } catch (err) {
      setError("Failed to load locations. Please try again.");
      console.error("Error fetching locations:", err);
    } finally {
      setLoading(false);
    }
  }, [programme?.id]);

  // Reset state when dialog opens/closes
  useEffect(() => {
    if (isOpen) {
      setStep(1);
      setError(null);
      setSelectedRegion(null);
      setCentre(null);
      setFormData({});
      setFormErrors({});
      fetchLocations();
    }
  }, [isOpen, programme?.id, fetchLocations]);

  // Fetch form schema when moving to form step
  const fetchFormSchema = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRegistrationForm();

      if (data && data.length > 0) {
        setFormSchema(data[0]); // Get the first form
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

  // Handle location step completion
  const handleLocationNext = () => {
    if (selectedRegion && selectedCentre) {
      setStep(2);
      fetchFormSchema();
    }
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
          const phoneRegex = /^[0-9+\-\s()]+$/;
          if (!phoneRegex.test(value) || value.length < 10) {
            errors[field.field_name] = "Please enter a valid phone number";
          }
        }
      });

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

    try {
      setSubmitting(true);

      // Prepare submission data - include course info since it's required
      const submissionData = {
        ...formData,
        course: programme.title, // Add course title since it's required by API
        programme_id: programme.id,
        region_id: selectedRegion.id,
        centre_id: selectedCentre.id,
        form_uuid: formSchema.uuid,
      };

      await submitRegistration(submissionData);
      setStep(3); // Success step
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

    const baseClasses = `w-full px-4 py-3 border rounded-lg transition-colors duration-200 ${
      hasError
        ? "border-red-300 focus:border-red-500 focus:ring-red-200"
        : "border-gray-300 focus:border-yellow-500 focus:ring-yellow-200"
    } focus:ring-2 focus:outline-none`;

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
            // Filter out any characters that aren't numbers, spaces, +, -, or parentheses
            const filteredValue = e.target.value.replace(/[^0-9+\-\s()]/g, "");
            handleFieldChange(field.field_name, filteredValue);
          }}
          onKeyPress={(e) => {
            // Prevent any key that isn't a number, space, +, -, or parentheses
            const allowedChars = /[0-9+\-\s()]/;
            if (
              !allowedChars.test(e.key) &&
              e.key !== "Backspace" &&
              e.key !== "Delete" &&
              e.key !== "Tab"
            ) {
              e.preventDefault();
            }
          }}
          className={baseClasses}
          placeholder={placeholder}
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

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        {/* Backdrop */}
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={onClose}
          className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        />

        {/* Dialog */}
        <motion.div
          initial={{ opacity: 0, scale: 0.9, y: 20 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.9, y: 20 }}
          className="relative bg-white rounded-3xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
        >
          {/* Close Button - Top Right, well clear of step indicator */}
          <button
            onClick={onClose}
            className="absolute top-4 right-4 z-50 p-2 bg-white/90 hover:bg-white text-gray-600 hover:text-gray-900 rounded-full shadow-lg backdrop-blur-sm transition-all duration-200 hover:shadow-xl"
          >
            <FiX className="w-5 h-5" />
          </button>

          <div className="flex max-h-[90vh]">
            {/* Left Side - Course Image (Half of dialog) */}
            <div className="hidden md:block w-1/2 relative">
              <Image
                src={
                  programme?.image ||
                  "/images/hero/Certified-Data-Protection-Manager.jpg"
                }
                alt={programme?.title}
                fill
                className="object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-br from-black/60 via-black/40 to-transparent" />

              {/* Course Info Overlay */}
              <div className="absolute inset-0 flex flex-col justify-between p-8">
                {/* Top - Simple Step Progress Indicator */}
                <div className="flex justify-center">
                  <div className="flex items-center space-x-3">
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-200 ${
                        step >= 1
                          ? "bg-yellow-400 text-gray-900"
                          : "bg-white/30 text-white"
                      }`}
                    >
                      1
                    </div>
                    <div
                      className={`w-12 h-1 rounded-full transition-colors duration-200 ${
                        step >= 2 ? "bg-yellow-400" : "bg-white/30"
                      }`}
                    ></div>
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-200 ${
                        step >= 2
                          ? "bg-yellow-400 text-gray-900"
                          : "bg-white/30 text-white"
                      }`}
                    >
                      2
                    </div>
                  </div>
                </div>

                {/* Bottom - Course details */}
                <div className="text-white">
                  <div className="mb-4">
                    <span className="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                      {programme?.category?.title}
                    </span>
                  </div>
                  <h3 className="text-2xl font-bold mb-3 leading-tight">
                    {programme?.title}
                  </h3>
                  <p className="text-white/90 text-sm leading-relaxed">
                    {programme?.sub_title ||
                      "Professional certification program"}
                  </p>
                  {programme?.duration && (
                    <div className="flex items-center space-x-2 mt-4 text-white/80">
                      <FiClock className="w-4 h-4" />
                      <span className="text-sm">{programme.duration}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Right Side - Content (Half of dialog) */}
            <div className="w-full md:w-1/2 flex flex-col">
              {/* Mobile Header with Steps */}
              <div className="md:hidden p-6 border-b border-gray-100">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h2 className="text-xl font-bold text-gray-900">
                      Registration
                    </h2>
                    <p className="text-sm text-gray-600">{programme?.title}</p>
                  </div>
                  <button
                    onClick={onClose}
                    className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    <FiX className="w-5 h-5" />
                  </button>
                </div>

                {/* Mobile Step Indicator */}
                <div className="flex items-center justify-center space-x-3">
                  <div
                    className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-200 ${
                      step >= 1
                        ? "bg-yellow-400 text-gray-900"
                        : "bg-gray-200 text-gray-500"
                    }`}
                  >
                    1
                  </div>
                  <div
                    className={`w-12 h-1 rounded-full transition-all duration-200 ${
                      step >= 2 ? "bg-yellow-400" : "bg-gray-200"
                    }`}
                  ></div>
                  <div
                    className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-200 ${
                      step >= 2
                        ? "bg-yellow-400 text-gray-900"
                        : "bg-gray-200 text-gray-500"
                    }`}
                  >
                    2
                  </div>
                </div>

                <p className="text-center text-sm text-gray-600 mt-3">
                  {step === 1
                    ? "Choose your preferred training location"
                    : step === 2
                    ? "Complete your registration details"
                    : "Registration completed successfully!"}
                </p>
              </div>

              {/* Desktop Header with Description */}
              <div className="hidden md:block p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                  <GhanaGradientText variant="red-yellow-green">
                    Registration
                  </GhanaGradientText>
                </h2>
                <p className="text-gray-600">
                  {step === 1
                    ? "Choose your preferred training location"
                    : step === 2
                    ? "Complete your registration details"
                    : "Registration completed successfully!"}
                </p>
              </div>

              {/* Content Area */}
              <div className="flex-1 overflow-y-auto">
                {error && (
                  <div className="m-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center space-x-3">
                    <FiAlertCircle className="w-5 h-5 text-red-600 flex-shrink-0" />
                    <p className="text-red-700">{error}</p>
                  </div>
                )}

                {/* Step 1: Location Selection */}
                {step === 1 && (
                  <div className="p-6">
                    <div className="flex items-center space-x-3 mb-6">
                      <div className="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center">
                        <FiMapPin className="w-5 h-5 text-white" />
                      </div>
                      <div>
                        <h3 className="text-xl font-semibold text-gray-900">
                          Select Training Location
                        </h3>
                        <p className="text-gray-600">
                          Choose your preferred region and training centre
                        </p>
                      </div>
                    </div>

                    {loading ? (
                      <div className="flex items-center justify-center py-12">
                        <FiLoader className="w-8 h-8 animate-spin text-yellow-600" />
                      </div>
                    ) : locations?.regions ? (
                      <div className="space-y-6">
                        {/* Region Selection */}
                        <div>
                          <label className="block text-sm font-medium text-gray-900 mb-3">
                            Select Region
                          </label>
                          <div className="grid gap-3">
                            {locations.regions.map((region) => (
                              <button
                                key={region.id}
                                onClick={() => {
                                  setSelectedRegion(region);
                                  setCentre(null);
                                }}
                                className={`p-4 rounded-lg border-2 text-left transition-all duration-200 ${
                                  selectedRegion?.id === region.id
                                    ? "border-yellow-400 bg-yellow-50"
                                    : "border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                                }`}
                              >
                                <div className="flex items-center justify-between">
                                  <div>
                                    <h4 className="font-medium text-gray-900">
                                      {region.title}
                                    </h4>
                                    <p className="text-sm text-gray-500">
                                      {region.centres.length} centre
                                      {region.centres.length !== 1 ? "s" : ""}{" "}
                                      available
                                    </p>
                                  </div>
                                  <FiChevronRight className="w-5 h-5 text-gray-400" />
                                </div>
                              </button>
                            ))}
                          </div>
                        </div>

                        {/* Centre Selection */}
                        {selectedRegion && (
                          <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.3 }}
                          >
                            <label className="block text-sm font-medium text-gray-900 mb-3">
                              Select Training Centre in {selectedRegion.title}
                            </label>
                            <div className="grid gap-3">
                              {selectedRegion.centres.map((centre) => (
                                <button
                                  key={centre.id}
                                  onClick={() => setCentre(centre)}
                                  className={`p-4 rounded-lg border-2 text-left transition-all duration-200 ${
                                    selectedCentre?.id === centre.id
                                      ? "border-yellow-400 bg-yellow-50"
                                      : "border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                                  }`}
                                >
                                  <h4 className="font-medium text-gray-900">
                                    {centre.title}
                                  </h4>
                                </button>
                              ))}
                            </div>
                          </motion.div>
                        )}

                        {/* Next Button */}
                        <div className="pt-4">
                          <Button
                            onClick={handleLocationNext}
                            disabled={!selectedRegion || !selectedCentre}
                            icon={FiChevronRight}
                            iconPosition="right"
                            className="w-full"
                          >
                            Continue to Registration Form
                          </Button>
                        </div>
                      </div>
                    ) : (
                      <div className="text-center py-12">
                        <p className="text-gray-600">
                          No locations available for this programme.
                        </p>
                      </div>
                    )}
                  </div>
                )}

                {/* Step 2: Registration Form */}
                {step === 2 && (
                  <div className="p-6">
                    <div className="flex items-center space-x-3 mb-6">
                      <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <FiUser className="w-5 h-5 text-white" />
                      </div>
                      <div>
                        <h3 className="text-xl font-semibold text-gray-900">
                          Registration Details
                        </h3>
                        <p className="text-gray-600">
                          Fill in your information to complete registration
                        </p>
                      </div>
                    </div>

                    {/* Selected Location Summary */}
                    <div className="bg-gray-50 rounded-lg p-4 mb-6">
                      <h4 className="font-medium text-gray-900 mb-2">
                        Selected Location
                      </h4>
                      <p className="text-sm text-gray-600">
                        <span className="font-medium">
                          {selectedCentre?.title}
                        </span>
                        <br />
                        {selectedRegion?.title}
                      </p>
                    </div>

                    {loading ? (
                      <div className="flex items-center justify-center py-12">
                        <FiLoader className="w-8 h-8 animate-spin text-yellow-600" />
                      </div>
                    ) : formSchema ? (
                      <form className="space-y-6" onSubmit={handleSubmit}>
                        {formSchema.schema
                          .filter((field) => field.field_name !== "course") // Hide course field
                          .map((field) => (
                            <div key={field.field_name}>
                              <label className="block text-sm font-medium text-gray-900 mb-2">
                                {field.title}
                                {field.validators?.required && (
                                  <span className="text-red-500 ml-1">*</span>
                                )}
                              </label>
                              {renderFormField(field)}
                              {formErrors[field.field_name] && (
                                <p className="mt-1 text-sm text-red-600">
                                  {formErrors[field.field_name]}
                                </p>
                              )}
                              {field.description &&
                                !formErrors[field.field_name] && (
                                  <p className="mt-1 text-sm text-gray-500">
                                    {field.description}
                                  </p>
                                )}
                            </div>
                          ))}

                        <div className="flex space-x-4 pt-4">
                          <Button
                            onClick={() => setStep(1)}
                            variant="outline"
                            className="flex-1"
                            type="button"
                          >
                            Back
                          </Button>
                          <Button
                            type="submit"
                            disabled={submitting}
                            icon={submitting ? FiLoader : FiCheckCircle}
                            className="flex-1"
                          >
                            {submitting
                              ? "Submitting..."
                              : "Submit Registration"}
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
                  </div>
                )}

                {/* Step 3: Success */}
                {step === 3 && (
                  <div className="p-6">
                    <div className="text-center py-12">
                      <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <FiCheckCircle className="w-8 h-8 text-green-600" />
                      </div>
                      <h3 className="text-2xl font-bold text-gray-900 mb-4">
                        Registration Successful!
                      </h3>
                      <p className="text-gray-600 mb-6 leading-relaxed">
                        {formSchema?.message_after_registration ||
                          "Thank you for registering! Further instructions will be sent to you via email/SMS."}
                      </p>
                      <Button onClick={onClose} variant="primary">
                        Close
                      </Button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
};

export default RegistrationDialog;
