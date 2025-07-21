"use client";

import { motion, useReducedMotion } from "framer-motion";
import Image from "next/image";
import Button from "./Button";
import { FiArrowRight } from "react-icons/fi";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { GhanaGradientBackground } from "@/components/GhanaGradients";

const AboutSection = ({ data }) => {
  const router = useRouter();
  const prefersReducedMotion = useReducedMotion();

  // Use only API data
  const sectionData = data?.section_items?.[0];
  const [imageSrc, setImageSrc] = useState(sectionData?.media?.url);

  const handleLearnMore = () => {
    if (sectionData?.slider_button_link) {
      if (sectionData.slider_button_link.startsWith("http")) {
        window.open(sectionData.slider_button_link, "_blank");
      } else {
        router.push(sectionData.slider_button_link);
      }
    }
  };

  const handleImageError = () => {
    // No fallback image
    setImageSrc(null);
  };

  // Optimized animation variants
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: prefersReducedMotion ? 0 : 0.15,
        duration: prefersReducedMotion ? 0.3 : 0.8,
      },
    },
  };

  const itemVariants = {
    hidden: {
      opacity: 0,
      y: prefersReducedMotion ? 0 : 20,
    },
    visible: {
      opacity: 1,
      y: 0,
      transition: {
        duration: prefersReducedMotion ? 0.3 : 0.6,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
  };

  // Don't render if no API data
  if (!sectionData) {
    return null;
  }

  return (
        <section className="relative overflow-hidden bg-gradient-to-br from-gray-50 via-white to-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20 relative z-10">
        <motion.div
          variants={containerVariants}
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: "-100px" }}
          className="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center"
        >
          {/* Mobile-first: Image first on mobile, content second */}
          <motion.div
            variants={itemVariants}
            className="order-1 lg:order-2 relative"
          >
            {/* Enhanced image container with Ghana theming */}
            <div className="relative group max-w-md mx-auto lg:max-w-none">
              {/* Ghana flag inspired border - subtle glow effect */}
              <div
                className="absolute -inset-4 rounded-3xl bg-gradient-to-r from-red-600/20 via-yellow-400/20 to-green-600/20 
                             opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-xl"
              ></div>

              {/* Main image container with responsive aspect ratios */}
              <div
                className="relative aspect-[3/4] sm:aspect-[4/5] lg:aspect-[4/6] xl:aspect-[3/4] 
                             rounded-2xl sm:rounded-3xl overflow-hidden 
                             shadow-lg group-hover:shadow-2xl transition-shadow duration-500
                             ring-1 ring-black/5 group-hover:ring-yellow-400/20"
              >
                {/* Background for loading state */}
                <div className="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200"></div>

                {imageSrc && (
                  <Image
                    src={imageSrc}
                    alt="H.E. John Dramani Mahama - President of the Republic of Ghana"
                    fill
                    className="object-cover transition-all duration-500 group-hover:scale-[1.02]"
                    priority
                    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 40vw"
                    onError={handleImageError}
                  />
                )}

                {/* Subtle overlay for better text contrast if needed */}
                <div
                  className="absolute inset-0 bg-gradient-to-t from-black/5 via-transparent to-transparent 
                               group-hover:from-black/10 transition-colors duration-500"
                ></div>
              </div>

              {/* Ghana star decoration - enhanced and responsive */}
              <motion.div
                initial={{ scale: 0, rotate: -180 }}
                whileInView={{ scale: 1, rotate: 0 }}
                transition={{
                  duration: prefersReducedMotion ? 0.3 : 0.8,
                  delay: prefersReducedMotion ? 0 : 0.5,
                  type: "spring",
                  stiffness: 200,
                }}
                viewport={{ once: true }}
                className="absolute -top-3 -right-3 sm:-top-4 sm:-right-4 w-10 h-10 sm:w-12 sm:h-12 z-10 
                          group-hover:scale-110 transition-transform duration-300"
              >
                <div
                  className="w-full h-full bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full 
                               flex items-center justify-center shadow-lg ring-2 ring-white"
                >
                  <svg
                    className="w-5 h-5 sm:w-6 sm:h-6 text-black"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </motion.div>
            </div>
          </motion.div>

          {/* Content - Enhanced mobile typography and spacing */}
          <motion.div
            variants={itemVariants}
            className="order-2 lg:order-1 space-y-6 sm:space-y-8 text-center lg:text-left"
          >
            {/* Section Header - Enhanced mobile typography */}
            <motion.div
              variants={itemVariants}
              className="space-y-3 sm:space-y-4"
            >
              <h2 className="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-gray-900 leading-tight">
                {sectionData.title}
              </h2>
            </motion.div>

            {/* Content - Improved mobile readability */}
            {sectionData.slider_description && (
              <motion.div
                variants={itemVariants}
                className="space-y-4 sm:space-y-6"
              >
                <div
                  className="text-base sm:text-lg lg:text-xl text-gray-700 leading-relaxed 
                           prose prose-lg max-w-none prose-p:mb-4 sm:prose-p:mb-6
                           prose-headings:text-gray-900 prose-headings:font-semibold
                           prose-strong:text-yellow-700 prose-a:text-green-600 prose-a:no-underline hover:prose-a:underline"
                  dangerouslySetInnerHTML={{
                    __html: sectionData.slider_description,
                  }}
                />
              </motion.div>
            )}

            {/* CTA Button - Enhanced mobile positioning */}
            {sectionData.slider_button_text && (
              <motion.div variants={itemVariants} className="pt-2 sm:pt-4">
                <Button
                  onClick={handleLearnMore}
                  icon={FiArrowRight}
                  variant="success"
                  size="large"
                  iconPosition="right"
                  className="w-full sm:w-auto font-semibold shadow-lg hover:shadow-xl 
                           transform hover:-translate-y-0.5 transition-all duration-200
                           text-sm sm:text-base px-6 sm:px-8 py-3 sm:py-4"
                >
                  {sectionData.slider_button_text}
                </Button>
              </motion.div>
            )}
          </motion.div>
        </motion.div>
      </div>
    </section>
  );
};

export default AboutSection;
