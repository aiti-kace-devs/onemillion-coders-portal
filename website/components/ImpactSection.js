"use client";

import { motion, AnimatePresence, useReducedMotion } from "framer-motion";
import {
  FiChevronLeft,
  FiChevronRight,
  FiArrowRight,
  FiUsers,
  FiBriefcase,
  FiTrendingUp,
  FiAward,
} from "react-icons/fi";
import { useState, useEffect } from "react";
import Button from "./Button";
import { GhanaGradientBar } from "@/components/GhanaGradients";

const ImpactSection = ({ data }) => {

  const [currentTestimonial, setCurrentTestimonial] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);
  const prefersReducedMotion = useReducedMotion();

  // Get API data
  const textDataBlock = data?.section_items?.find(item => item.blueprint === 'textdatablock');
  const successStories = data?.section_items?.find(item => item.blueprint === 'success_stories');

  // Use only API testimonials
  const testimonials = successStories?.stories?.map((story, index) => ({
    id: index + 1,
    quote: story.message?.replace(/"/g, '') || "",
    name: story.graduate_name,
    title: story.program_completed,
    company: story.sector,
  })) || [];

  // Use only API stats
  const stats = textDataBlock?.metrics?.map(metric => ({
    number: metric.number,
    label: metric.description?.toUpperCase() || "",
    icon: FiUsers, // Default icon
  })) || [];

  const nextTestimonial = () => {
    setCurrentTestimonial((prev) => (prev + 1) % testimonials.length);
    setIsAutoPlaying(false); // Stop auto-play when user interacts
  };

  const prevTestimonial = () => {
    setCurrentTestimonial(
      (prev) => (prev - 1 + testimonials.length) % testimonials.length
    );
    setIsAutoPlaying(false); // Stop auto-play when user interacts
  };

  // Auto-play functionality
  useEffect(() => {
    let interval;
    if (isAutoPlaying && testimonials.length > 0 && !prefersReducedMotion) {
      interval = setInterval(() => {
        setCurrentTestimonial((prev) => (prev + 1) % testimonials.length);
      }, 7000); // Change every 7 seconds
    }

    return () => {
      if (interval) {
        clearInterval(interval);
      }
    };
  }, [isAutoPlaying, testimonials.length, prefersReducedMotion]);

  // Resume auto-play after user stops interacting
  useEffect(() => {
    let timeout;
    if (!isAutoPlaying) {
      timeout = setTimeout(() => {
        setIsAutoPlaying(true);
      }, 10000); // Resume after 10 seconds of no interaction
    }

    return () => {
      if (timeout) {
        clearTimeout(timeout);
      }
    };
  }, [isAutoPlaying]);

  // Don't render if no API data
  if (!textDataBlock && !successStories) {
    return null;
  }

  // Animation variants for testimonial transitions
  const testimonialVariants = {
    enter: {
      x: 50,
      opacity: 0,
    },
    center: {
      x: 0,
      opacity: 1,
      transition: {
        duration: 0.3,
        ease: "easeOut",
      },
    },
    exit: {
      x: -50,
      opacity: 0,
      transition: {
        duration: 0.2,
        ease: "easeIn",
      },
    },
  };

  return (
    <section className="section-spacing bg-gradient-to-b from-gray-900 via-gray-900 to-gray-800 relative overflow-hidden">
      {/* Enhanced Ghana-themed Background Elements */}
      <div className="absolute inset-0">
        {/* Subtle grid pattern */}
        <div className="absolute inset-0 opacity-5 bg-[linear-gradient(45deg,rgba(255,255,255,0.1)_1px,transparent_1px),linear-gradient(-45deg,rgba(255,255,255,0.1)_1px,transparent_1px)] bg-[size:60px_60px]"></div>

        {/* Ghana Flag Color Ambient glows */}
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-yellow-500/5 rounded-full blur-3xl"></div>
        <div className="absolute bottom-1/4 right-1/4 w-80 h-80 bg-green-600/5 rounded-full blur-3xl"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-red-600/3 rounded-full blur-3xl"></div>

        {/* Ghana Map Pattern */}
        <div className="absolute top-20 left-20 w-48 h-48 opacity-10">
          <div
            className="w-full h-full"
            style={{
              background: `radial-gradient(circle, rgba(251, 191, 36, 0.3) 1px, transparent 1px)`,
              backgroundSize: "10px 10px",
            }}
          ></div>
        </div>

        {/* Ghana Flag Bar - Top */}
        <GhanaGradientBar height="1px" position="top" opacity="50" />

        {/* Ghana Flag Bar - Bottom */}
        <div className="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-600/30 via-yellow-500/30 to-red-600/30"></div>

        {/* Ghana Star Pattern */}
        <div className="absolute bottom-20 right-20 w-16 h-16 opacity-20">
          <svg
            className="w-full h-full text-yellow-500"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        {/* Section Header */}
        <motion.div
          initial={{ opacity: 0, y: 15 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.3 }}
          className="text-center mb-16"
        >
          <motion.div
            initial={{ opacity: 0, y: 15 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.3, delay: 0.05 }}
            className="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-gray-900 text-sm font-semibold rounded-full mb-6"
          >
            <FiTrendingUp size={16} />
            {textDataBlock?.text_data_caption}
          </motion.div>
          {textDataBlock?.text_data_name && (
          <h2 className="heading-lg text-white content-spacing max-w-4xl mx-auto">
              {textDataBlock.text_data_name}
          </h2>
          )}
          {textDataBlock?.text_data_description && (
          <p className="text-lead text-gray-400 max-w-3xl mx-auto">
              <span dangerouslySetInnerHTML={{ __html: textDataBlock.text_data_description }} />
          </p>
          )}
        </motion.div>

        {/* Statistics Grid */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.3, delay: 0.1 }}
          className="mb-20"
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {stats.map((stat, index) => {
              const icons = [FiUsers, FiBriefcase, FiTrendingUp, FiAward];
              const IconComponent = icons[index];

              return (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 15 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.25, delay: 0.03 * index }}
                  className="group relative bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 hover:border-yellow-500/40 transition-all duration-250 ease-out hover:bg-white/10 hover:shadow-xl hover:shadow-yellow-500/10"
                >
                  {/* Subtle glow effect on hover */}
                  <div className="absolute inset-0 rounded-2xl bg-gradient-to-br from-yellow-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-250 ease-out"></div>

                  <div className="relative flex flex-col items-center text-center">
                    <motion.div
                      className="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mb-4 shadow-lg"
                      whileHover={{ scale: 1.05 }}
                      transition={{ duration: 0.15 }}
                    >
                      <IconComponent size={24} className="text-gray-900" />
                    </motion.div>
                    <motion.div
                      className="text-3xl font-bold text-white mb-2"
                      initial={{ opacity: 0 }}
                      whileInView={{ opacity: 1 }}
                      viewport={{ once: true }}
                      transition={{ duration: 0.3, delay: 0.05 * index }}
                    >
                      {stat.number}
                    </motion.div>
                    <div className="text-gray-400 text-sm font-medium uppercase tracking-wide">
                      {stat.label}
                    </div>

                    {/* Subtle bottom accent */}
                    <div className="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-8 h-0.5 bg-gradient-to-r from-transparent via-yellow-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-250 ease-out"></div>
                  </div>
                </motion.div>
              );
            })}
          </div>
        </motion.div>

        {/* Success Stories */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.3, delay: 0.15 }}
          className="mb-16"
        >
          <div className="text-center mb-12">
            <h3 className="heading-md text-white content-spacing">
              Success Stories
            </h3>
            <p className="text-lead text-gray-400 max-w-2xl mx-auto">
              Real graduates, real careers, real impact in Ghana&apos;s tech
              industry
            </p>
          </div>

          <div className="max-w-4xl mx-auto">
            <div className="group bg-white/5 backdrop-blur-sm rounded-2xl p-8 md:p-10 border border-white/10 relative overflow-hidden hover:border-white/20 transition-all duration-300 ease-out hover:shadow-xl hover:shadow-yellow-500/5">
              {/* Testimonial background glow */}
              <div className="absolute inset-0 bg-gradient-to-br from-yellow-500/3 via-transparent to-blue-500/3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-out"></div>

              <AnimatePresence mode="wait">
                <motion.div
                  key={currentTestimonial}
                  variants={testimonialVariants}
                  initial="enter"
                  animate="center"
                  exit="exit"
                  style={{ willChange: "transform, opacity" }}
                  className="relative flex flex-col md:flex-row gap-8 items-center"
                >
                  {/* Profile Section */}
                  <div className="flex-shrink-0 text-center md:text-left">
                    <motion.div
                      className="w-16 h-16 mx-auto md:mx-0 mb-4 rounded-full bg-yellow-500 flex items-center justify-center text-gray-900 text-2xl font-bold"
                      initial={{ scale: 0 }}
                      animate={{ scale: 1 }}
                      transition={{ delay: 0.1, duration: 0.2 }}
                    >
                      {testimonials[currentTestimonial].name.charAt(0)}
                    </motion.div>
                    <motion.h4
                      className="text-lg font-semibold text-white mb-1"
                      initial={{ opacity: 0, y: 5 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.15, duration: 0.2 }}
                    >
                      {testimonials[currentTestimonial].name}
                    </motion.h4>
                    <motion.p
                      className="text-gray-400 text-sm mb-2"
                      initial={{ opacity: 0, y: 5 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.2, duration: 0.2 }}
                    >
                      {testimonials[currentTestimonial].title}
                    </motion.p>
                    <motion.p
                      className="text-gray-500 text-xs"
                      initial={{ opacity: 0, y: 5 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.25, duration: 0.2 }}
                    >
                      {testimonials[currentTestimonial].company}
                    </motion.p>
                  </div>

                  {/* Quote Section */}
                  <div className="flex-1">
                    <motion.blockquote
                      className="text-lg text-gray-300 mb-4 leading-relaxed italic"
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.1, duration: 0.25 }}
                    >
                      &quot;{testimonials[currentTestimonial].quote}&quot;
                    </motion.blockquote>

                    {/* <motion.div 
                      className="flex items-center gap-2 text-yellow-500 text-sm"
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ delay: 0.5, duration: 0.3 }}
                    >
                      <FiAward size={16} />
                      <span className="font-medium">Graduate Success Story</span>
                    </motion.div> */}
                  </div>
                </motion.div>
              </AnimatePresence>

              {/* Navigation - Enhanced */}
              {/* <div className="flex gap-2 mt-2 z-[1000]">
                <motion.button
                  onClick={prevTestimonial}
                  className="cursor-pointer w-8 h-8 rounded-full bg-white/10 border border-white/20 text-white hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200 flex items-center justify-center hover:shadow-lg hover:shadow-yellow-500/20"
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.95 }}
                >
                  <FiChevronLeft size={14} />
                </motion.button>
                <motion.button
                  onClick={nextTestimonial}
                  className="cursor-pointer w-8 h-8 rounded-full bg-white/10 border border-white/20 text-white hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200 flex items-center justify-center hover:shadow-lg hover:shadow-yellow-500/20"
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.95 }}
                >
                  <FiChevronRight size={14} />
                </motion.button>
              </div> */}

              {/* Enhanced Auto-play Indicator */}
              <div className="absolute bottom-6 right-8 flex items-center gap-2">
                {testimonials.map((_, index) => (
                  <motion.div
                    key={index}
                    className={`w-2 h-2 rounded-full transition-all duration-200 ease-out cursor-pointer ${
                      index === currentTestimonial
                        ? "bg-yellow-500 shadow-lg shadow-yellow-500/50"
                        : "bg-white/30 hover:bg-white/50"
                    }`}
                    onClick={() => {
                      setCurrentTestimonial(index);
                      setIsAutoPlaying(false);
                    }}
                    whileHover={{ scale: 1.15 }}
                    whileTap={{ scale: 0.85 }}
                    transition={{ duration: 0.15 }}
                  />
                ))}
              </div>
            </div>
          </div>
        </motion.div>

        {/* Call to Action */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.3, delay: 0.2 }}
          className="text-center relative"
        >
          {/* Subtle background glow for CTA */}
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="w-64 h-32 bg-yellow-500/5 rounded-full blur-2xl"></div>
          </div>

          <div className="relative">
            {/* <motion.div
              whileHover={{ scale: 1.02 }}
              transition={{ duration: 0.2 }}
            >
              <Button 
                variant="primary"
                size="large"
                icon={FiArrowRight}
                iconPosition="right"
                onClick={() => ('View success stories')}
                className="shadow-xl hover:shadow-2xl hover:shadow-yellow-500/20"
              >
                View All Success Stories
              </Button>
            </motion.div> */}

            <motion.p
              initial={{ opacity: 0 }}
              whileInView={{ opacity: 1 }}
              viewport={{ once: true }}
              transition={{ duration: 0.3, delay: 0.25 }}
              className="text-gray-500 text-sm mt-4 max-w-md mx-auto"
            >
              Join thousands of graduates building successful tech careers
              across Ghana
            </motion.p>

            {/* Decorative elements */}
            <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-16 h-0.5 bg-gradient-to-r from-transparent via-yellow-500/30 to-transparent"></div>
            <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-24 h-0.5 bg-gradient-to-r from-transparent via-yellow-500/20 to-transparent"></div>
          </div>
        </motion.div>
      </div>
    </section>
  );
};

export default ImpactSection;
