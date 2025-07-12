"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
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

const HeroSlider = () => {
  const [currentSlide, setCurrentSlide] = useState(0);
  const [isPlaying, setIsPlaying] = useState(true);

  // Navigation functions
  const goToNext = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const goToPrevious = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  const slides = [
    {
      id: 1,
      image: "/images/hero/1million4.jpg",
      title: "Join One Million Coders",
      description:
        "Become part of Africa's largest coding community. Learn, grow, and build the future with cutting-edge technology skills.",
      cta: "Start Your Journey",
      color: "from-blue-600/70 to-purple-700/70",
      to: "https://onemillioncoders.gov.gh/",
    },
    {
      id: 2,
      // image: "/images/hero/Certified-Data-Protection-Manager.jpg",
      image: "/images/courses/data-protection-manager.jpg",
      title: "Certified Data Protection Manager",
      description:
        "Become a certified expert in data protection and privacy. Lead organizations in compliance with global data protection regulations.",
      cta: "Get Certified",
      color: "from-emerald-600/70 to-teal-700/70",
      to: "https://onemillioncoders.gov.gh/protection-sup-course",
    },
    {
      id: 3,
      // image: "/images/hero/Certified-Data-Protection-Officer.jpg",
      image: "/images/courses/dpo.JPG",
      title: "Certified Data Protection Officer",
      description:
        "Advance your career as a Data Protection Officer. Gain the expertise to navigate complex privacy landscapes and drive organizational compliance.",
      cta: "Register",
      color: "from-amber-600/70 to-orange-700/70",
      to: "https://onemillioncoders.gov.gh/certified-dpf-course",
    },
  ];

  // Auto-play functionality
  useEffect(() => {
    if (!isPlaying) return;

    const interval = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 12000); // Change slide every 8 seconds

    return () => clearInterval(interval);
  }, [isPlaying, slides.length]);

  // Slide variants for smooth animations
  const slideVariants = {
    enter: {
      opacity: 0,
      scale: 1.05,
    },
    center: {
      opacity: 1,
      scale: 1,
      transition: {
        duration: 1.2,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
    exit: {
      opacity: 0,
      scale: 0.95,
      transition: {
        duration: 0.6,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
  };

  const contentVariants = {
    enter: {
      y: 20,
      opacity: 0,
    },
    center: {
      y: 0,
      opacity: 1,
      transition: {
        duration: 0.8,
        delay: 0.4,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
    exit: {
      y: -10,
      opacity: 0,
      transition: {
        duration: 0.3,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
  };

  const current = slides[currentSlide];

  return (
    <div className="relative h-[85vh] min-h-[600px] max-h-[900px] overflow-hidden bg-gray-900">
      {/* Ghana Flag Bar - Top */}
      {/* <div className="absolute top-0 left-0 right-0 h-1 z-20 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600">
        <div className="absolute inset-0 bg-gradient-to-r from-red-600/90 via-yellow-400/90 to-green-600/90"></div>
      </div> */}

      {/* Ghana Map Pattern Overlay */}
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
            initial={{ scale: 1.1 }}
            animate={{ scale: 1 }}
            transition={{ duration: 8, ease: "linear" }}
            className="absolute inset-0"
          >
            <Image
              src={current.image}
              alt={current.title}
              fill
              className="object-cover"
              priority
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
                className="text-white space-y-8 max-w-3xl"
              >
                {/* Main Heading */}
                {/* Title */}
                <div className="space-y-6">
                  <motion.h1
                    initial={{ opacity: 0, y: 30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      duration: 0.8,
                      delay: 0.2,
                      ease: [0.25, 0.1, 0.25, 1],
                    }}
                    className="heading-xl text-white drop-shadow-lg"
                  >
                    {current.title}
                  </motion.h1>
                </div>

                {/* Sub Content */}
                <motion.p
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    duration: 0.8,
                    delay: 0.4,
                    ease: [0.25, 0.1, 0.25, 1],
                  }}
                  className="text-lead text-gray-100 leading-relaxed max-w-2xl drop-shadow-md"
                >
                  {current.description}
                </motion.p>

                {/* CTA */}
                <motion.div
                  initial={{ opacity: 0, y: 20, scale: 0.9 }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  transition={{
                    duration: 0.6,
                    delay: 0.6,
                    ease: [0.25, 0.1, 0.25, 1],
                  }}
                  className="pt-6"
                >
                  <Button
                    onClick={() => window.open(current.to, "_blank")}
                    icon={FiArrowRight}
                    variant="primary"
                    size="large"
                    iconPosition="right"
                    className="text-black font-semibold shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-200"
                  >
                    {current.cta}
                  </Button>
                </motion.div>
              </motion.div>
            </AnimatePresence>
          </div>
        </div>
      </div>

      {/* Slide Navigation - Bottom Left Pills */}
      <div className="absolute bottom-8 left-0 right-0 z-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-start">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.8 }}
              className="flex space-x-2"
            >
              {slides.map((_, index) => (
                <motion.button
                  key={index}
                  onClick={() => setCurrentSlide(index)}
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  transition={{
                    duration: 0.3,
                    delay: 1 + index * 0.1,
                    type: "spring",
                    stiffness: 200,
                  }}
                  whileHover={{ scale: 1.2 }}
                  whileTap={{ scale: 0.9 }}
                  className={`h-[3px] rounded-full transition-all duration-300 ease-out backdrop-blur-sm ${
                    index === currentSlide
                      ? "bg-white w-8 shadow-sm"
                      : "bg-white/40 w-8 hover:bg-white/60 hover:shadow-sm"
                  }`}
                />
              ))}
            </motion.div>
          </div>
        </div>
      </div>

      {/* Play/Pause Control */}
      <motion.button
        onClick={() => setIsPlaying(!isPlaying)}
        initial={{ opacity: 0, scale: 0 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{
          duration: 0.5,
          delay: 1.2,
          type: "spring",
          stiffness: 200,
        }}
        whileHover={{ scale: 1.15, rotate: 5 }}
        whileTap={{ scale: 0.95 }}
        className="absolute top-1/2 right-8 transform -translate-y-1/2 z-20 bg-white/10 backdrop-blur-md rounded-full p-3 text-white hover:bg-white/20 transition-all duration-200 shadow-lg"
      >
        <motion.div
          key={isPlaying ? "pause" : "play"}
          initial={{ scale: 0, rotate: 180 }}
          animate={{ scale: 1, rotate: 0 }}
          transition={{ duration: 0.3, type: "spring", stiffness: 300 }}
        >
          {isPlaying ? (
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14h3V5H8zm5 0v14h3V5h-3z" />
            </svg>
          ) : (
            <FiPlay className="w-5 h-5" />
          )}
        </motion.div>
      </motion.button>

      {/* Elegant Arrow Navigation */}
      <div className="absolute bottom-8 right-0 z-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-end">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.9 }}
              className="flex items-center space-x-3 bg-black/20 backdrop-blur-sm rounded-full p-2"
            >
              {/* Previous Arrow */}
              <motion.button
                onClick={goToPrevious}
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{
                  duration: 0.3,
                  delay: 1.2,
                  type: "spring",
                  stiffness: 200,
                }}
                whileHover={{
                  scale: 1.1,
                  backgroundColor: "rgba(255,255,255,0.2)",
                }}
                whileTap={{ scale: 0.9 }}
                className="rounded-full p-2 text-white/70 hover:text-white transition-all duration-200"
              >
                <FiChevronLeft className="w-4 h-4" />
              </motion.button>

              {/* Divider */}
              <div className="w-px h-4 bg-white/30"></div>

              {/* Next Arrow */}
              <motion.button
                onClick={goToNext}
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{
                  duration: 0.3,
                  delay: 1.3,
                  type: "spring",
                  stiffness: 200,
                }}
                whileHover={{
                  scale: 1.1,
                  backgroundColor: "rgba(255,255,255,0.2)",
                }}
                whileTap={{ scale: 0.9 }}
                className="rounded-full p-2 text-white/70 hover:text-white transition-all duration-200"
              >
                <FiChevronRight className="w-4 h-4" />
              </motion.button>
            </motion.div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HeroSlider;
