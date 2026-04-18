"use client";

import { motion, useReducedMotion } from "framer-motion";
import { getProgrammesData } from "../services";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import { useState, useRef, useEffect } from "react";
import { useRouter } from "next/navigation";
import { FiArrowRight } from "react-icons/fi";
import Button from "./Button";
import ProgrammeCard from "./ProgrammeCard";
import CoursesSkeleton from "./CoursesSkeleton";

const CoursesSection = ({ categories: apiCategories }) => {
  const safeCategories = Array.isArray(apiCategories) ? apiCategories : [];
  const [selectedCategory, setSelectedCategory] = useState("All");
  const [isMobile, setIsMobile] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [programmes, setProgrammes] = useState([]);
  const categoryScrollRef = useRef(null);
  const prefersReducedMotion = useReducedMotion();
  const router = useRouter();

  // Fetch programmes data
  const fetchProgrammes = async (categoryId = null) => {
    try {
      let url = "/programmes?sort=title&order=asc&limit=9";
      if (categoryId) {
        url = `/programmes/category/${categoryId}?sort=title&order=asc&limit=9`;
      }

      const data = await getProgrammesData(url);
      
      // Ensure data is an array
      const dataArray = Array.isArray(data) ? data : (data?.programmes || data?.data || []);

      
      // More lenient filtering for active programmes
      const activeProgrammes = dataArray.filter(programme => {
        // Check for truthy status values (true, "true", 1, "1", "active", etc.)
        // If status field doesn't exist, include the programme by default
        return programme.status === undefined || 
               (programme.status && programme.status !== false && programme.status !== "false" && programme.status !== 0);
      });
      
      setProgrammes(activeProgrammes);
      return activeProgrammes;
    } catch (error) {
      console.error("Error in fetchProgrammes:", error);
      setProgrammes([]);
      throw error; // Re-throw so handleCategoryClick can catch it
    }
  };

  // Initial fetch
  useEffect(() => {
    const initialFetch = async () => {
      try {
        await fetchProgrammes();
      } catch (error) {
        console.error("Error in initial fetch:", error);
        setProgrammes([]);
      } finally {
        setIsLoading(false);
      }
    };
    
    initialFetch();
  }, []);

  // Detect mobile device (debounced)
  useEffect(() => {
    let timeoutId;
    const checkMobile = () => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => {
        setIsMobile(window.innerWidth < 768);
      }, 150);
    };

    setIsMobile(window.innerWidth < 768);
    window.addEventListener("resize", checkMobile);
    return () => {
      clearTimeout(timeoutId);
      window.removeEventListener("resize", checkMobile);
    };
  }, []);

  // Get categories from API (safe when API fails and returns null/undefined)
  const categories =
    safeCategories.length > 0
      ? [
          "All",
          ...safeCategories
            .filter((cat) => cat?.status)
            .map((cat) => cat?.title ?? "")
            .filter(Boolean),
        ]
      : ["All"];

  // No need to filter locally since we're getting filtered data from API
  const filteredProgrammes = programmes;

  // Handle category selection and refetch data
  const handleCategoryClick = async (category) => {
    setSelectedCategory(category);
    setIsLoading(true);
    
    try {
      if (category === "All") {
        const data = await fetchProgrammes();
      } else {
        const categoryId = safeCategories.find(
          (cat) => cat?.title === category
        )?.id;
        if (categoryId) {
          const data = await fetchProgrammes(categoryId);
        } else {
          setProgrammes([]); // Clear programmes if no category ID found
        }
      }
    } catch (error) {
      console.error("Error handling category click:", error);
      setProgrammes([]); // Clear programmes on error
    } finally {
      setIsLoading(false);
    }
  };

  // Category color mapping (matte tones)
  const categoryColors = {
    "Cybersecurity": "bg-red-50 text-red-700 border-red-100",
    "Data Protection": "bg-blue-50 text-blue-700 border-blue-100",
    "Artificial Intelligence": "bg-purple-50 text-purple-700 border-purple-100",
    "Software Development": "bg-emerald-50 text-emerald-700 border-emerald-100",
    "Cloud Computing": "bg-orange-50 text-orange-700 border-orange-100",
    "IT Support": "bg-indigo-50 text-indigo-700 border-indigo-100",
    "Data Analyst": "bg-teal-50 text-teal-700 border-teal-100",
    "Digital Marketing": "bg-pink-50 text-pink-700 border-pink-100",
    "Project Management": "bg-amber-50 text-amber-700 border-amber-100",
    "UI / UX Design": "bg-violet-50 text-violet-700 border-violet-100",
    "Digital Literacy": "bg-cyan-50 text-cyan-700 border-cyan-100",
  };

  // Enhanced animation variants
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: prefersReducedMotion ? 0 : 0.04,
        duration: prefersReducedMotion ? 0.3 : 0.6,
      },
    },
  };

  const cardVariants = {
    hidden: {
      opacity: 0,
      y: prefersReducedMotion ? 0 : 30,
      scale: prefersReducedMotion ? 1 : 0.95,
    },
    visible: {
      opacity: 1,
      y: 0,
      scale: 1,
      transition: {
        duration: prefersReducedMotion ? 0.3 : 0.6,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
  };

  const categoryVariants = {
    hidden: { opacity: 0, x: prefersReducedMotion ? 0 : 20 },
    visible: {
      opacity: 1,
      x: 0,
      transition: {
        duration: prefersReducedMotion ? 0.2 : 0.4,
        ease: [0.25, 0.1, 0.25, 1],
      },
    },
  };

  return (
    <section className="bg-gray-100 relative overflow-hidden">
      <div className="section-spacing relative">
        {/* Ghana Map Pattern Background */}
        <div className="absolute inset-0 opacity-8">
          <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-gray-400/10 rounded-full blur-3xl"></div>

          {/* Ghana Map Dot Pattern */}
          <div className="absolute top-20 right-20 w-64 h-64 opacity-20">
            <div
              className="w-full h-full"
              style={{
                background: `radial-gradient(circle, #10b981 1px, transparent 1px)`,
                backgroundSize: "12px 12px",
              }}
            ></div>
          </div>

          {/* Ghana Flag Ribbon */}
          <GhanaGradientBar height="1px" position="bottom-full" opacity="30" />
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          {/* Section Header */}
          <div className="mb-12 sm:mb-16">
            <motion.h2
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: prefersReducedMotion ? 0.3 : 0.4 }}
              viewport={{ once: true }}
              className="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6"
            >
              Courses
            </motion.h2>
            <motion.p
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{
                duration: prefersReducedMotion ? 0.3 : 0.4,
                delay: prefersReducedMotion ? 0 : 0.05,
              }}
              viewport={{ once: true }}
              className="text-base sm:text-lg lg:text-xl text-gray-600 max-w-4xl mb-8 sm:mb-12 leading-relaxed"
            >
              Advance your career with our comprehensive courses designed for
              industry leaders. Gain specialized expertise and earn globally
              recognized certifications.
            </motion.p>

            {/* Enhanced Category Filter with Horizontal Scroll */}
            <motion.div
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true }}
              variants={{
                hidden: { opacity: 0, y: prefersReducedMotion ? 0 : 15 },
                visible: {
                  opacity: 1,
                  y: 0,
                  transition: {
                    duration: prefersReducedMotion ? 0.3 : 0.4,
                    delay: prefersReducedMotion ? 0 : 0.1,
                    staggerChildren: prefersReducedMotion ? 0 : 0.03,
                  },
                },
              }}
              className="relative"
            >
              {/* Scroll container with hidden scrollbars */}
              <div
                ref={categoryScrollRef}
                className="flex gap-3 sm:gap-4 overflow-x-auto pb-2 mb-8 sm:mb-12 scrollbar-hide scroll-smooth"
                style={{
                  scrollSnapType: "x mandatory",
                  WebkitOverflowScrolling: "touch",
                }}
              >
                {categories.map((category) => (
                  <motion.button
                    key={category}
                    variants={categoryVariants}
                    onClick={() => handleCategoryClick(category)}
                    whileHover={prefersReducedMotion ? {} : { scale: 1.02 }}
                    whileTap={prefersReducedMotion ? {} : { scale: 0.98 }}
                    className={`flex-shrink-0 px-4 sm:px-6 py-2.5 sm:py-3 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer scroll-snap-align-start touch-manipulation min-w-fit whitespace-nowrap ${
                      selectedCategory === category
                        ? "bg-yellow-500 text-gray-900 border-yellow-500 shadow-md"
                        : category === "All"
                        ? "bg-gray-200 text-gray-800 border-gray-300 hover:bg-gray-300 hover:shadow-sm"
                        : `${
                            categoryColors[category] ||
                            "bg-gray-100 text-gray-800 border-gray-200"
                          } hover:shadow-sm`
                    }`}
                  >
                    {category}
                  </motion.button>
                ))}
              </div>

              {/* Fade gradients for scroll indication */}
              <div className="absolute top-0 left-0 w-8 h-full bg-gradient-to-r from-gray-100 to-transparent pointer-events-none md:hidden"></div>
              <div className="absolute top-0 right-0 w-8 h-full bg-gradient-to-l from-gray-100 to-transparent pointer-events-none md:hidden"></div>
            </motion.div>
          </div>

          {/* Enhanced Courses Grid with Fixed Animations */}
          <motion.div
            key={`grid-${selectedCategory}-${programmes.length}`}
            variants={containerVariants}
            initial="hidden"
            animate="visible"
            className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8"
          >
            {isLoading
              ? // Skeleton loading state
                [...Array(6)].map((_, index) => (
                  <motion.div
                    key={`skeleton-${index}`}
                    variants={cardVariants}
                    className="opacity-100" // Ensure skeleton is always visible
                  >
                    <CoursesSkeleton />
                  </motion.div>
                ))
              : filteredProgrammes.length > 0 ? (
                filteredProgrammes.map((programme, index) => (
                  <motion.div
                    key={`${programme.id}-${selectedCategory}-${index}`}
                    variants={cardVariants}
                    whileHover={
                      prefersReducedMotion
                        ? {}
                        : {
                            y: -8,
                            transition: {
                              duration: 0.2,
                              ease: [0.25, 0.1, 0.25, 1],
                            },
                          }
                    }
                    className="opacity-100" // Ensure card is always visible
                  >
                    <ProgrammeCard programme={programme} />
                  </motion.div>
                ))
              ) : (
                // No programmes found message
                <motion.div
                  key="no-programmes"
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.4 }}
                  className="col-span-full text-center py-12"
                >
                  <div className="text-gray-500 text-lg">
                    No programmes found for &ldquo;{selectedCategory}&rdquo;
                  </div>
                  <p className="text-gray-400 mt-2">
                    Try selecting a different category or check back later.
                  </p>
                </motion.div>
              )}
          </motion.div>

          {/* View All Courses CTA */}
          <div className="text-center mt-12 sm:mt-16">
            <motion.div
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{
                duration: prefersReducedMotion ? 0.3 : 0.6,
                delay: prefersReducedMotion ? 0 : 0.3,
              }}
              viewport={{ once: true }}
            >
              <Button
                variant="outline"
                size="large"
                icon={FiArrowRight}
                iconPosition="right"
                onClick={() => router.push("/programmes")}
                className="shadow-md hover:shadow-lg transition-all duration-200"
              >
                Browse Programmes
              </Button>
            </motion.div>
          </div>
        </div>
      </div>

      {/* Custom CSS for hiding scrollbars */}
      <style jsx>{`
        .scrollbar-hide {
          -ms-overflow-style: none;
          scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </section>
  );
};

export default CoursesSection;
