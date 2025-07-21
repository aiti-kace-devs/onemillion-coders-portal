"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import { motion, AnimatePresence, useReducedMotion } from "framer-motion";
import Image from "next/image";
import { FiX } from "react-icons/fi";
import { GhanaGradientText } from "@/components/GhanaGradients";

const SplashScreen = ({ onDismiss }) => {
  const [isVisible, setIsVisible] = useState(true);
  const [progress, setProgress] = useState(0);
  const [imageSrc, setImageSrc] = useState("/images/jdm-1.jpg");
  const prefersReducedMotion = useReducedMotion();
  const animationFrameRef = useRef();
  const startTimeRef = useRef();

  const DISPLAY_DURATION = 6000; // 6 seconds

  const handleImageError = () => {
    setImageSrc("/images/jdm-1-old.png");
  };

  const handleDismiss = useCallback(() => {
    setIsVisible(false);
    setTimeout(() => {
      onDismiss();
    }, prefersReducedMotion ? 150 : 400); // Faster for reduced motion users
  }, [onDismiss, prefersReducedMotion]);

  // Optimized progress animation using requestAnimationFrame
  const updateProgress = useCallback((timestamp) => {
    if (!startTimeRef.current) {
      startTimeRef.current = timestamp;
    }

    const elapsed = timestamp - startTimeRef.current;
    const newProgress = Math.min((elapsed / DISPLAY_DURATION) * 100, 100);
    
    setProgress(newProgress);

    if (newProgress < 100) {
      animationFrameRef.current = requestAnimationFrame(updateProgress);
    } else {
      handleDismiss();
    }
  }, [handleDismiss]);

  useEffect(() => {
    // Check if user has opted out of seeing the splash screen
    const hasOptedOut = localStorage.getItem("splashScreenOptOut");
    if (hasOptedOut === "true") {
      setIsVisible(false);
      onDismiss();
      return;
    }

    // Start optimized progress animation
    animationFrameRef.current = requestAnimationFrame(updateProgress);

    // Fallback auto-dismiss timer
    const dismissTimer = setTimeout(() => {
      handleDismiss();
    }, DISPLAY_DURATION + 100);

    return () => {
      if (animationFrameRef.current) {
        cancelAnimationFrame(animationFrameRef.current);
      }
      clearTimeout(dismissTimer);
    };
  }, [onDismiss, handleDismiss, updateProgress]);

  const handleDontShowAgain = (checked) => {
    if (checked) {
      localStorage.setItem("splashScreenOptOut", "true");
    } else {
      localStorage.removeItem("splashScreenOptOut");
    }
  };

  const handleSkip = () => {
    if (animationFrameRef.current) {
      cancelAnimationFrame(animationFrameRef.current);
    }
    handleDismiss();
  };

  // Optimized animation variants
  const containerVariants = {
    initial: { 
      opacity: 0,
      scale: prefersReducedMotion ? 1 : 0.98
    },
    animate: { 
      opacity: 1,
      scale: 1,
      transition: { 
        duration: prefersReducedMotion ? 0.2 : 0.3,
        ease: "easeOut"
      }
    },
    exit: {
      opacity: 0,
      scale: prefersReducedMotion ? 1 : 0.96,
      transition: { 
        duration: prefersReducedMotion ? 0.15 : 0.4,
        ease: "easeInOut"
      }
    }
  };

  const contentVariants = {
    initial: { 
      opacity: 0, 
      y: prefersReducedMotion ? 0 : 15
    },
    animate: {
      opacity: 1,
      y: 0,
      transition: { 
        duration: prefersReducedMotion ? 0.2 : 0.5,
        ease: "easeOut"
      }
    }
  };

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          variants={containerVariants}
          initial="initial"
          animate="animate"
          exit="exit"
          className="fixed inset-0 z-[9999] bg-white will-change-transform"
          style={{ 
            backfaceVisibility: 'hidden',
            perspective: '1000px'
          }}
        >
          {/* Optimized Progress Bar */}
          <div className="absolute top-0 left-0 right-0 h-2 bg-gray-200 z-20">
            <div
              className="h-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 will-change-transform"
              style={{
                width: `${progress}%`,
                transform: 'translate3d(0, 0, 0)', // GPU acceleration
                transition: prefersReducedMotion ? 'none' : 'width 0.1s linear'
              }}
            />
          </div>

          {/* Skip Button */}
          <motion.button
            whileHover={prefersReducedMotion ? {} : { scale: 1.05 }}
            whileTap={prefersReducedMotion ? {} : { scale: 0.95 }}
            onClick={handleSkip}
            className="absolute top-4 right-4 z-30 p-3 hover:bg-gray-100 rounded-full transition-colors duration-200 will-change-transform"
            style={{ transform: 'translate3d(0, 0, 0)' }}
          >
            <FiX className="w-5 h-5 text-gray-600" />
          </motion.button>

          {/* Main Content Container */}
          <div className="flex flex-col items-center justify-center h-full px-6 text-center will-change-transform">
            {/* President Image */}
            <motion.div
              variants={contentVariants}
              initial="initial"
              animate="animate"
              transition={{ delay: prefersReducedMotion ? 0 : 0.1 }}
              className="relative mb-8 will-change-transform"
              style={{ transform: 'translate3d(0, 0, 0)' }}
            >
              <div className="aspect-[4/6] relative w-48 h-60 sm:w-56 sm:h-72 md:w-64 md:h-80 bg-gray-100 rounded-2xl overflow-hidden">
                <Image
                  src={imageSrc}
                  alt="H.E. John Dramani Mahama"
                  fill
                  className="object-cover"
                  priority
                  sizes="(max-width: 768px) 192px, (max-width: 1024px) 224px, 256px"
                  onError={handleImageError}
                  style={{ transform: 'translate3d(0, 0, 0)' }}
                />
              </div>
            </motion.div>

            {/* Content */}
            <motion.div
              variants={contentVariants}
              initial="initial"
              animate="animate"
              transition={{ delay: prefersReducedMotion ? 0 : 0.2 }}
              className="max-w-md space-y-4 will-change-transform"
              style={{ transform: 'translate3d(0, 0, 0)' }}
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
            variants={contentVariants}
            initial="initial"
            animate="animate"
            transition={{ delay: prefersReducedMotion ? 0 : 0.3 }}
            className="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-30 will-change-transform"
            style={{ transform: 'translate3d(-50%, 0, 0)' }}
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