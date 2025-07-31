"use client";

import { useState, useEffect, useCallback, useRef, useMemo, memo } from "react";
import { motion, AnimatePresence, useReducedMotion } from "framer-motion";
import Image from "next/image";
import { FiX } from "react-icons/fi";
import { GhanaGradientText } from "@/components/GhanaGradients";

// Memoized static content to prevent unnecessary re-renders
const SplashContent = memo(
  ({
    imageSrc,
    handleImageError,
    contentVariants,
    prefersReducedMotion,
    transformStyle,
  }) => (
    <>
      {/* Main Content Container */}
      <div className="flex flex-col items-center justify-center h-full px-6 text-center will-change-transform">
        {/* President Image */}
        <motion.div
          variants={contentVariants}
          initial="initial"
          animate="animate"
          transition={{ delay: prefersReducedMotion ? 0 : 0.1 }}
          className="relative mb-8 will-change-transform"
          style={transformStyle}
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
              style={transformStyle}
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
          style={transformStyle}
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
              &ldquo;The youth of Ghana are our greatest asset. Their talent,
              intelligence, and creativity form the bedrock of our nation&apos;s
              future success. Through initiatives like One Million Coders, the
              NDC government is committed to creating meaningful opportunities
              that will empower young Ghanaians to unlock their full
              potential.&rdquo;
            </p>
            <p className="text-sm text-gray-500 font-medium">
              — H.E. John Dramani Mahama
            </p>
          </div>
        </motion.div>
      </div>
    </>
  )
);

SplashContent.displayName = "SplashContent";

// Memoized checkbox component to prevent re-renders
const DontShowToggle = memo(({ handleDontShowAgain }) => (
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
));

DontShowToggle.displayName = "DontShowToggle";

const SplashScreen = ({ onDismiss }) => {
  const [isVisible, setIsVisible] = useState(true);
  const [imageSrc, setImageSrc] = useState("/images/jdm-1.jpg");
  const prefersReducedMotion = useReducedMotion();
  const animationFrameRef = useRef();
  const startTimeRef = useRef();
  const progressBarRef = useRef();

  const DISPLAY_DURATION = 6000; // 6 seconds

  const handleImageError = useCallback(() => {
    setImageSrc("/images/jdm-1-old.png");
  }, []);

  const handleDismiss = useCallback(() => {
    setIsVisible(false);
    setTimeout(
      () => {
        onDismiss();
      },
      prefersReducedMotion ? 150 : 400
    ); // Faster for reduced motion users
  }, [onDismiss, prefersReducedMotion]);

  // Optimized progress animation using direct DOM manipulation
  const updateProgress = useCallback(
    (timestamp) => {
      if (!startTimeRef.current) {
        startTimeRef.current = timestamp;
      }

      const elapsed = timestamp - startTimeRef.current;
      const progress = Math.min((elapsed / DISPLAY_DURATION) * 100, 100);

      // Direct DOM manipulation to avoid re-renders
      if (progressBarRef.current) {
        progressBarRef.current.style.width = `${progress}%`;
      }

      if (progress < 100) {
        animationFrameRef.current = requestAnimationFrame(updateProgress);
      } else {
        handleDismiss();
      }
    },
    [handleDismiss, DISPLAY_DURATION]
  );

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

  const handleDontShowAgain = useCallback((checked) => {
    if (checked) {
      localStorage.setItem("splashScreenOptOut", "true");
    } else {
      localStorage.removeItem("splashScreenOptOut");
    }
  }, []);

  const handleSkip = useCallback(() => {
    if (animationFrameRef.current) {
      cancelAnimationFrame(animationFrameRef.current);
    }
    handleDismiss();
  }, [handleDismiss]);

  // Memoized animation variants to prevent recreation on every render
  const containerVariants = useMemo(
    () => ({
      initial: {
        opacity: 0,
        scale: prefersReducedMotion ? 1 : 0.98,
      },
      animate: {
        opacity: 1,
        scale: 1,
        transition: {
          duration: prefersReducedMotion ? 0.2 : 0.3,
          ease: "easeOut",
        },
      },
      exit: {
        opacity: 0,
        scale: prefersReducedMotion ? 1 : 0.96,
        transition: {
          duration: prefersReducedMotion ? 0.15 : 0.4,
          ease: "easeInOut",
        },
      },
    }),
    [prefersReducedMotion]
  );

  const contentVariants = useMemo(
    () => ({
      initial: {
        opacity: 0,
        y: prefersReducedMotion ? 0 : 15,
      },
      animate: {
        opacity: 1,
        y: 0,
        transition: {
          duration: prefersReducedMotion ? 0.2 : 0.5,
          ease: "easeOut",
        },
      },
    }),
    [prefersReducedMotion]
  );

  // Memoized inline styles to prevent recreation
  const containerStyle = useMemo(
    () => ({
      backfaceVisibility: "hidden",
      perspective: "1000px",
    }),
    []
  );

  const transformStyle = useMemo(
    () => ({
      transform: "translate3d(0, 0, 0)",
    }),
    []
  );

  const centerTransformStyle = useMemo(
    () => ({
      transform: "translate3d(-50%, 0, 0)",
    }),
    []
  );

  return (
    <AnimatePresence>
      {isVisible && (
        <motion.div
          variants={containerVariants}
          initial="initial"
          animate="animate"
          exit="exit"
          className="fixed inset-0 z-[9999] bg-white will-change-transform"
          style={containerStyle}
        >
          {/* Optimized Progress Bar - No React re-renders */}
          <div className="absolute top-0 left-0 right-0 h-2 bg-gray-200 z-20">
            <div
              ref={progressBarRef}
              className="h-full bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 will-change-transform"
              style={{
                width: "0%",
                transform: "translate3d(0, 0, 0)", // GPU acceleration
                transition: prefersReducedMotion ? "none" : undefined,
              }}
            />
          </div>

          {/* Skip Button */}
          <motion.button
            whileHover={prefersReducedMotion ? {} : { scale: 1.05 }}
            whileTap={prefersReducedMotion ? {} : { scale: 0.95 }}
            onClick={handleSkip}
            className="absolute top-4 right-4 z-30 p-3 hover:bg-gray-100 rounded-full transition-colors duration-200 will-change-transform"
            style={transformStyle}
          >
            <FiX className="w-5 h-5 text-gray-600" />
          </motion.button>

          <SplashContent
            imageSrc={imageSrc}
            handleImageError={handleImageError}
            contentVariants={contentVariants}
            prefersReducedMotion={prefersReducedMotion}
            transformStyle={transformStyle}
          />

          {/* Don't Show Again Toggle */}
          <motion.div
            variants={contentVariants}
            initial="initial"
            animate="animate"
            transition={{ delay: prefersReducedMotion ? 0 : 0.3 }}
            className="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-30 will-change-transform"
            style={centerTransformStyle}
          >
            <DontShowToggle handleDontShowAgain={handleDontShowAgain} />
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default SplashScreen;
