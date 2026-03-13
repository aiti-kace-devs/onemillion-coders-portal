"use client";

import { useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { motion } from "framer-motion";
import { FiSearch, FiFilter, FiClock } from "react-icons/fi";
import { getProgrammesData, getCategoriesData } from "../../services";
import ProgrammeCard from "../../components/ProgrammeCard";
import ProgrammeSkeleton from "../../components/ProgrammeSkeleton";

export default function ProgrammesClient({ initialCategory }) {
  const searchParams = useSearchParams();
  const userId = searchParams.get("user_id");
  const centreId = searchParams.get("centre_id");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("All");
  const [selectedDuration, setSelectedDuration] = useState("All");
  const [programmes, setProgrammes] = useState([]);
  const [categories, setCategories] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch data
  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);
        const [programmesData, categoriesData] = await Promise.all([
          getProgrammesData(),
          getCategoriesData()
        ]);
        setProgrammes(programmesData || []);
        setCategories(["All", ...(categoriesData || []).map(cat => cat.title)]);
      } catch (err) {
        console.error("Error fetching data:", err);
        setError("Failed to load programmes. Please try again later.");
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, []);

  // Handle URL parameters and initial category
  useEffect(() => {
    const fetchCategoryTitle = async () => {
      if (initialCategory) {
        try {
          const categoriesData = await getCategoriesData();
          const category = categoriesData?.find(cat => cat.id.toString() === initialCategory);
          if (category) {
            setSelectedCategory(category.title);
          }
        } catch (error) {
          console.error('Error fetching category:', error);
        }
      } else {
        const categoryParam = searchParams.get("category");
        if (categoryParam) {
          setSelectedCategory(decodeURIComponent(categoryParam));
        }
      }
    };

    fetchCategoryTitle();
  }, [searchParams, initialCategory]);

  // Filter programmes
  const filteredProgrammes = programmes.filter(program => {
    const matchesSearch = 
      program.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      program.sub_title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      program.category?.title.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesCategory =
      selectedCategory === "All" || program.category?.title === selectedCategory;

    const durationHours = parseInt(program.duration);
    const matchesDuration =
      selectedDuration === "All" ||
      (selectedDuration === "Short" && durationHours <= 100) ||
      (selectedDuration === "Medium" && durationHours > 100 && durationHours <= 200) ||
      (selectedDuration === "Long" && durationHours > 200);

    return matchesSearch && matchesCategory && matchesDuration;
  });

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <p className="text-red-600 mb-2">{error}</p>
          <button 
            onClick={() => window.location.reload()}
            className="text-yellow-600 underline hover:text-yellow-700"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header Section */}
      <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center"
          >
            <h1 className="text-4xl lg:text-6xl font-bold mb-6">
              Training <span className="text-yellow-400">Programmes</span>
            </h1>
            <p className="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
              Empowering the next generation of digital talent with comprehensive coding and technology programmes.
            </p>

            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">
                  {isLoading ? "-" : programmes.length}
                </div>
                <div className="text-gray-300">Training Programs</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">1M+</div>
                <div className="text-gray-300">Target Trainees</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">
                  {isLoading ? "-" : categories.length - 1}
                </div>
                <div className="text-gray-300">Categories</div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Search and Filters */}
      <section className="py-8 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {/* Search */}
              <div className="lg:col-span-1">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Search Programs
                </label>
                <div className="relative">
                  <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    placeholder="Search programmes..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                  />
                </div>
              </div>

              {/* Category Filter */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Category
                </label>
                <div className="relative">
                  <FiFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <select
                    value={selectedCategory}
                    onChange={(e) => setSelectedCategory(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 appearance-none bg-white"
                    disabled={isLoading}
                  >
                    {categories.map((category) => (
                      <option key={category} value={category}>
                        {category}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Duration Filter */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Duration
                </label>
                <div className="relative">
                  <FiClock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <select
                    value={selectedDuration}
                    onChange={(e) => setSelectedDuration(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 appearance-none bg-white"
                  >
                    <option value="All">All Durations</option>
                    <option value="Short">Short (≤100 hrs)</option>
                    <option value="Medium">Medium (101-200 hrs)</option>
                    <option value="Long">Long (200+ hrs)</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Results Count */}
            <div className="mt-6 pt-6 border-t border-gray-100">
              <div className="flex items-center justify-between">
                <div className="text-sm text-gray-600">
                  {isLoading ? (
                    <div className="h-4 w-32 bg-gray-200 rounded animate-pulse"></div>
                  ) : (
                    <>
                      <span className="font-semibold text-gray-900">
                        {filteredProgrammes.length}
                      </span>{" "}
                      of{" "}
                      <span className="font-semibold text-gray-900">
                        {programmes.length}
                      </span>{" "}
                      programmes
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Programs Grid */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {isLoading ? (
              // Skeleton loading state
              [...Array(6)].map((_, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                >
                  <ProgrammeSkeleton />
                </motion.div>
              ))
            ) : filteredProgrammes.length === 0 ? (
              <div className="col-span-3 text-center py-16">
                <FiSearch className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-2xl font-semibold text-gray-900 mb-2">
                  No programmes found
                </h3>
                <p className="text-gray-600">
                  Try adjusting your search or filter criteria
                </p>
              </div>
            ) : (
              filteredProgrammes.map((programme, index) => (
                <motion.div
                  key={programme.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                >
                  <ProgrammeCard programme={programme} userId={userId} centreId={centreId} />
                </motion.div>
              ))
            )}
          </div>
        </div>
      </section>
    </div>
  );
} 