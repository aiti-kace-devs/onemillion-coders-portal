"use client";

import { useState, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import { FiX } from "react-icons/fi";
import { GhanaGradientText } from "@/components/GhanaGradients";

const SplashScreen = ({ onDismiss }) => {
  const [isVisible, setIsVisible] = useState(true);
  const [progress, setProgress] = useState(0);

  const DISPLAY_DURATION = 6000; // 6 seconds

  const handleDismiss = useCallback(() => {
    setIsVisible(false);
    setTimeout(() => {
      onDismiss();
    }, 600); // Wait for exit animation to complete
  }, [onDismiss]);

  useEffect(() => {
    // Check if user has opted out of seeing the splash screen
    const hasOptedOut = localStorage.getItem("splashScreenOptOut");
    if (hasOptedOut === "true") {
      setIsVisible(false);
      onDismiss();
      return;
    }

    // Progress bar animation
    const progressInterval = setInterval(() => {
      setProgress((prev) => {
        if (prev >= 100) {
          clearInterval(progressInterval);
          handleDismiss();
          return 100;
        }
        return prev + (100 / (DISPLAY_DURATION / 100));
      });
    }, 100);

    // Auto-dismiss after duration
    const dismissTimer = setTimeout(() => {
      handleDismiss();
    }, DISPLAY_DURATION);

    return () => {
      clearInterval(progressInterval);
      clearTimeout(dismissTimer);
    };
  }, [onDismiss, handleDismiss]);

  const handleDontShowAgain = (checked) => {
    if (checked) {
      localStorage.setItem("splashScreenOptOut", "true");
    } else {
      localStorage.removeItem("splashScreenOptOut");
    }
  };

  const handleSkip = () => {
    handleDismiss();
  };

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ 
            opacity: 0,
            scale: 0.95,
            filter: "blur(10px)",
            transition: { duration: 0.4, ease: "easeInOut" }
          }}
          transition={{ duration: 0.3, ease: "easeOut" }}
          className="fixed inset-0 z-[9999] bg-white"
        >
          {/* Progress Bar */}
          <div className="absolute top-0 left-0 right-0 h-2 bg-gray-200 z-20">
            <motion.div
              initial={{ width: 0 }}
              animate={{ width: `${progress}%` }}
              transition={{ duration: 0.1, ease: "linear" }}
              className="h-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 shadow-lg"
            />
          </div>

          {/* Skip Button */}
          <motion.button
            whileHover={{ scale: 1.1 }}
            whileTap={{ scale: 0.9 }}
            onClick={handleSkip}
            className="absolute top-4 right-4 z-30 p-3 hover:bg-gray-100 rounded-full transition-colors duration-200"
          >
            <FiX className="w-5 h-5 text-gray-600" />
          </motion.button>

          {/* Main Content */}
          <div className="flex flex-col items-center justify-center h-full px-6 text-center">
            {/* President Image */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1, ease: "easeOut" }}
              className="relative mb-8"
            >
              <div className="aspect-[4/6] relative w-48 h-60 sm:w-56 sm:h-72 md:w-64 md:h-80">
                <Image
                  src="/images/jdm-1.png"
                  alt="H.E. John Dramani Mahama"
                  fill
                  className="object-fill"
                  priority
                />
              </div>
            </motion.div>

            {/* Content */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.3, ease: "easeOut" }}
              className="max-w-md space-y-4"
            >
              {/* Title */}
              <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
                Welcome to
                <GhanaGradientText className="block">
                  One Million Coders
                </GhanaGradientText>
              </h1>

              {/* Presidential Quote */}
              <div className="space-y-3">
                <p className="text-base sm:text-lg text-gray-700 leading-relaxed italic">
                  &ldquo;We are committed to building the largest community of skilled programmers in Africa, empowering our youth with the digital skills needed for the future economy.&rdquo;
                </p>
                <p className="text-sm text-gray-500 font-medium">
                  — H.E. John Dramani Mahama
                </p>
              </div>
            </motion.div>
          </div>

          {/* Don't Show Again Toggle */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.5, ease: "easeOut" }}
            className="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-30"
          >
            <label className="flex items-center space-x-2 cursor-pointer bg-white/80 backdrop-blur-sm px-4 py-2 rounded-full shadow-md hover:shadow-lg transition-all duration-200 hover:bg-white/90">
              <input
                type="checkbox"
                onChange={(e) => handleDontShowAgain(e.target.checked)}
                className="w-4 h-4 text-yellow-400 bg-gray-100 border-gray-300 rounded focus:ring-yellow-400 focus:ring-2"
              />
              <span className="text-sm text-gray-700 font-medium">
                Don&apos;t show again
              </span>
            </label>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default SplashScreen; 