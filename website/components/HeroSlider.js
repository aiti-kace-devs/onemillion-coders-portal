"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence, useReducedMotion } from "framer-motion";
import Image from "next/image";
import Button from "./Button";
import {
  FiArrowRight,
  FiPlay,
  FiUsers,
  FiAward,
  FiChevronLeft,
  FiChevronRight,
} from "react-icons/fi";

const HeroSlider = ({ data }) => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isPlaying, setIsPlaying] = useState(true);
  const [isMobile, setIsMobile] = useState(false);
  const prefersReducedMotion = useReducedMotion();

  // Detect mobile device
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 768);
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Use only API data
  const slides = data?.section_items?.map((item, index) => ({
    id: index + 1,
    image: item.media?.url,
    title: item.title,
    description: item.slider_description?.replace(/<[^>]*>/g, '') || "", // Strip HTML
    cta: item.slider_button_text,
    color: index === 0 ? "from-blue-600/70 to-purple-700/70" : 
           index === 1 ? "from-emerald-600/70 to-teal-700/70" : 
           "from-purple-600/70 to-pink-700/70",
    to: item.slider_button_link,
  })) || [];

  // Navigation functions
  const goToNext = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const goToPrevious = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  // Auto-play functionality - reduced interval on mobile
  useEffect(() => {
    if (!isPlaying || prefersReducedMotion) return;

    const interval = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, isMobile ? 8000 : 12000); // Faster transitions on mobile

    return () => clearInterval(interval);
  }, [isPlaying, slides.length, isMobile, prefersReducedMotion]);

  // Optimized slide variants with reduced motion support
  const slideVariants = {
    enter: {
      opacity: 0,
      scale: prefersReducedMotion ? 1 : (isMobile ? 1.02 : 1.05),
    },
    center: {
      opacity: 1,
      scale: 1,
      transition: {
        duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.8 : 1.2),
        ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
      },
    },
    exit: {
      opacity: 0,
      scale: prefersReducedMotion ? 1 : (isMobile ? 0.98 : 0.95),
      transition: {
        duration: prefersReducedMotion ? 0.2 : (isMobile ? 0.4 : 0.6),
        ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
      },
    },
  };

  // Optimized content variants
  const contentVariants = {
    enter: {
      y: prefersReducedMotion ? 0 : (isMobile ? 10 : 20),
      opacity: 0,
    },
    center: {
      y: 0,
      opacity: 1,
      transition: {
        duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.8),
        delay: prefersReducedMotion ? 0 : (isMobile ? 0.2 : 0.4),
        ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
      },
    },
    exit: {
      y: prefersReducedMotion ? 0 : (isMobile ? -5 : -10),
      opacity: 0,
      transition: {
        duration: prefersReducedMotion ? 0.2 : (isMobile ? 0.2 : 0.3),
        ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
      },
    },
  };

  // Don't render if no API data
  if (!slides || slides.length === 0) {
    return null;
  }

  const current = slides[currentSlide];

  return (
    <div className="relative h-[100vh] sm:h-[85vh] min-h-[500px] sm:min-h-[600px] max-h-[700px] sm:max-h-[900px] overflow-hidden bg-gray-900">
      {/* Ghana Flag Bar - Top */}
      {/* <div className="absolute top-0 left-0 right-0 h-1 z-20 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600">
        <div className="absolute inset-0 bg-gradient-to-r from-red-600/90 via-yellow-400/90 to-green-600/90"></div>
      </div> */}

      {/* Ghana Map Pattern Overlay - Hidden on mobile for performance */}
      {!isMobile && (
        <div className="absolute inset-0 z-5 opacity-5">
          <div className="absolute right-10 top-1/2 transform -translate-y-1/2 w-64 h-64">
            <div
              className="w-full h-full bg-white/10 rounded-full"
              style={{
                background: `radial-gradient(circle, transparent 20%, rgba(255,255,255,0.1) 20.5%, rgba(255,255,255,0.1) 21%, transparent 21.5%), 
                           radial-gradient(circle, transparent 20%, rgba(255,255,255,0.1) 20.5%, rgba(255,255,255,0.1) 21%, transparent 21.5%)`,
                backgroundSize: "8px 8px",
                backgroundPosition: "0 0, 4px 4px",
              }}
            ></div>
          </div>
        </div>
      )}

      {/* Background Slides */}
      <AnimatePresence mode="wait">
        <motion.div
          key={currentSlide}
          variants={slideVariants}
          initial="enter"
          animate="center"
          exit="exit"
          className="absolute inset-0"
        >
          <motion.div
            initial={{ scale: prefersReducedMotion ? 1 : (isMobile ? 1.02 : 1.1) }}
            animate={{ scale: 1 }}
            transition={{ 
              duration: prefersReducedMotion ? 0 : (isMobile ? 6 : 8), 
              ease: "linear" 
            }}
            className="absolute inset-0"
          >
            <Image
              src={current.image}
              alt={current.title}
              fill
              className="object-cover"
              priority
              sizes="100vw"
            />
          </motion.div>
          {/* Enhanced Gradient Overlay for better contrast */}
          <div
            className={`absolute inset-0 bg-gradient-to-br ${current.color}`}
          />
          {/* Enhanced vignette effect for better text readability */}
          <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-black/20 to-transparent" />
          <div className="absolute inset-0 bg-gradient-to-r from-black/30 via-transparent to-transparent" />
        </motion.div>
      </AnimatePresence>

      {/* Content */}
      <div className="relative z-10 h-full flex items-center">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
          <div className="flex items-center justify-start">
            {/* Left Content */}
            <AnimatePresence mode="wait">
              <motion.div
                key={`content-${currentSlide}`}
                variants={contentVariants}
                initial="enter"
                animate="center"
                exit="exit"
                className="text-white space-y-4 sm:space-y-6 lg:space-y-8 max-w-full sm:max-w-3xl"
              >
                {/* Title */}
                <div className="space-y-4 sm:space-y-6">
                  <motion.h1
                    initial={{ opacity: 0, y: prefersReducedMotion ? 0 : (isMobile ? 15 : 30) }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.8),
                      delay: prefersReducedMotion ? 0 : (isMobile ? 0.1 : 0.2),
                      ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
                    }}
                    className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight text-white drop-shadow-lg"
                  >
                    {current.title}
                  </motion.h1>
                </div>

                {/* Sub Content */}
                <motion.p
                  initial={{ opacity: 0, y: prefersReducedMotion ? 0 : (isMobile ? 10 : 20) }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.8),
                    delay: prefersReducedMotion ? 0.1 : (isMobile ? 0.2 : 0.4),
                    ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
                  }}
                  className="text-base sm:text-lg lg:text-xl text-gray-100 leading-relaxed max-w-full sm:max-w-2xl drop-shadow-md"
                >
                  {current.description}
                </motion.p>

                {/* CTA */}
                <motion.div
                  initial={{ 
                    opacity: 0, 
                    y: prefersReducedMotion ? 0 : (isMobile ? 10 : 20), 
                    scale: prefersReducedMotion ? 1 : (isMobile ? 1 : 0.9) 
                  }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  transition={{
                    duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.5 : 0.6),
                    delay: prefersReducedMotion ? 0.2 : (isMobile ? 0.3 : 0.6),
                    ease: prefersReducedMotion ? "easeInOut" : [0.25, 0.1, 0.25, 1],
                  }}
                  className="pt-4 sm:pt-6"
                >
                  <Button
                    onClick={() => window.open(current.to, "_blank")}
                    icon={FiArrowRight}
                    variant="primary"
                    size={isMobile ? "medium" : "large"}
                    iconPosition="right"
                    className="text-black font-semibold shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-200 text-sm sm:text-base"
                  >
                    {current.cta}
                  </Button>
                </motion.div>
              </motion.div>
            </AnimatePresence>
          </div>
        </div>
      </div>

      {/* Mobile-optimized Slide Navigation */}
      <div className="absolute bottom-4 sm:bottom-8 left-0 right-0 z-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-center sm:justify-start">
            <motion.div
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : (isMobile ? 10 : 20) }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ 
                duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.8), 
                delay: prefersReducedMotion ? 0.3 : (isMobile ? 0.4 : 0.8) 
              }}
              className="flex space-x-3 bg-black/20 backdrop-blur-sm rounded-full p-2 sm:p-1 sm:bg-transparent sm:backdrop-blur-none"
            >
              {slides.map((_, index) => (
                <motion.button
                  key={index}
                  onClick={() => setCurrentSlide(index)}
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  transition={{
                    duration: prefersReducedMotion ? 0.2 : 0.3,
                    delay: prefersReducedMotion ? 0 : (isMobile ? 0.5 + index * 0.05 : 1 + index * 0.1),
                    type: prefersReducedMotion ? "tween" : "spring",
                    stiffness: 200,
                  }}
                  whileHover={prefersReducedMotion ? {} : { scale: 1.2 }}
                  whileTap={prefersReducedMotion ? {} : { scale: 0.9 }}
                  className={`h-[4px] sm:h-[3px] rounded-full transition-all duration-300 ease-out backdrop-blur-sm ${
                    index === currentSlide
                      ? "bg-white w-10 sm:w-8 shadow-sm"
                      : "bg-white/40 w-10 sm:w-8 hover:bg-white/60 hover:shadow-sm"
                  }`}
                />
              ))}
            </motion.div>
          </div>
        </div>
      </div>

      {/* Touch-friendly Arrow Navigation - Responsive positioning */}
      <div className={`absolute z-20 ${
        isMobile 
          ? "bottom-16 left-0 right-0"  // Mobile: above indicators, centered
          : "bottom-4 sm:bottom-8 right-4 sm:right-0"  // Desktop: bottom-right corner
      }`}>
        <div className={isMobile ? "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" : "sm:max-w-7xl sm:mx-auto sm:px-4 sm:px-6 lg:px-8"}>
          <div className={`flex items-center ${isMobile ? "justify-center" : "justify-end"}`}>
            <motion.div
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : (isMobile ? 10 : 20) }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ 
                duration: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.8), 
                delay: prefersReducedMotion ? 0.3 : (isMobile ? 0.6 : 0.9) 
              }}
              className={`flex items-center justify-center bg-black/30 backdrop-blur-md rounded-full shadow-lg ${
                isMobile ? "p-2" : "p-1.5"
              }`}
            >
              {/* Previous Arrow */}
              <motion.button
                onClick={goToPrevious}
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{
                  duration: prefersReducedMotion ? 0.2 : 0.3,
                  delay: prefersReducedMotion ? 0 : (isMobile ? 0.7 : 1.2),
                  type: prefersReducedMotion ? "tween" : "spring",
                  stiffness: 200,
                }}
                whileHover={prefersReducedMotion ? {} : {
                  scale: 1.1,
                  backgroundColor: "rgba(255,255,255,0.2)",
                }}
                whileTap={prefersReducedMotion ? {} : { scale: 0.9 }}
                className={`flex items-center justify-center rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 touch-manipulation ${
                  isMobile ? "w-8 h-8" : "w-7 h-7"
                }`}
                aria-label="Previous slide"
              >
                <FiChevronLeft className={isMobile ? "w-4 h-4" : "w-3.5 h-3.5"} />
              </motion.button>

              {/* Divider */}
              <div className={`bg-white/30 ${isMobile ? "w-px h-5 mx-3" : "w-px h-4 mx-2"}`}></div>

              {/* Next Arrow */}
              <motion.button
                onClick={goToNext}
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{
                  duration: prefersReducedMotion ? 0.2 : 0.3,
                  delay: prefersReducedMotion ? 0.1 : (isMobile ? 0.8 : 1.3),
                  type: prefersReducedMotion ? "tween" : "spring",
                  stiffness: 200,
                }}
                whileHover={prefersReducedMotion ? {} : {
                  scale: 1.1,
                  backgroundColor: "rgba(255,255,255,0.2)",
                }}
                whileTap={prefersReducedMotion ? {} : { scale: 0.9 }}
                className={`flex items-center justify-center rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200 touch-manipulation ${
                  isMobile ? "w-8 h-8" : "w-7 h-7"
                }`}
                aria-label="Next slide"
              >
                <FiChevronRight className={isMobile ? "w-4 h-4" : "w-3.5 h-3.5"} />
              </motion.button>
            </motion.div>
          </div>
        </div>
      </div>

      {/* Play/Pause Control - Desktop only, repositioned */}
      {!isMobile && (
        <motion.button
          onClick={() => setIsPlaying(!isPlaying)}
          initial={{ opacity: 0, scale: 0 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{
            duration: prefersReducedMotion ? 0.3 : 0.5,
            delay: prefersReducedMotion ? 0.4 : 1.4,
            type: prefersReducedMotion ? "tween" : "spring",
            stiffness: 200,
          }}
          whileHover={prefersReducedMotion ? {} : { scale: 1.1 }}
          whileTap={prefersReducedMotion ? {} : { scale: 0.95 }}
          className="absolute top-1/2 right-6 transform -translate-y-1/2 z-10 bg-white/10 backdrop-blur-md rounded-full p-2.5 text-white hover:bg-white/20 transition-all duration-200 shadow-lg"
        >
          <motion.div
            key={isPlaying ? "pause" : "play"}
            initial={prefersReducedMotion ? {} : { scale: 0, rotate: 90 }}
            animate={{ scale: 1, rotate: 0 }}
            transition={prefersReducedMotion ? {} : { duration: 0.3, type: "spring", stiffness: 300 }}
          >
            {isPlaying ? (
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14h3V5H8zm5 0v14h3V5h-3z" />
              </svg>
            ) : (
              <FiPlay className="w-4 h-4" />
            )}
          </motion.div>
        </motion.button>
      )}
    </div>
  );
};

export default HeroSlider;
