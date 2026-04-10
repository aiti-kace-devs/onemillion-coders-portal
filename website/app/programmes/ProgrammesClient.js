"use client";

import { useState, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import { FiSearch, FiFilter, FiClock, FiX, FiGlobe, FiArrowUp, FiChevronDown, FiSliders, FiArrowLeft } from "react-icons/fi";
import { getProgrammesData, getCategoriesData } from "../../services";
import ProgrammeCard from "../../components/ProgrammeCard";
import ProgrammeSkeleton from "../../components/ProgrammeSkeleton";

export default function ProgrammesClient({ initialCategory }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const userId = searchParams.get("user_id");
  const centreId = searchParams.get("centre_id");
  const token = searchParams.get("token");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("All");
  const [selectedDuration, setSelectedDuration] = useState("All");
  const [selectedMode, setSelectedMode] = useState("All");
  const [sortBy, setSortBy] = useState("default");
  const [showFilters, setShowFilters] = useState(false);
  const [programmes, setProgrammes] = useState([]);
  const [categories, setCategories] = useState([]);
  const [deliveryModes, setDeliveryModes] = useState(["All"]);
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
        const modes = [...new Set((programmesData || []).map(p => p.mode_of_delivery).filter(Boolean))];
        setDeliveryModes(["All", ...modes]);
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

  // Check if any filters are active
  const hasActiveFilters = searchTerm || selectedCategory !== "All" || selectedDuration !== "All" || selectedMode !== "All" || sortBy !== "default";

  const clearAllFilters = () => {
    setSearchTerm("");
    setSelectedCategory("All");
    setSelectedDuration("All");
    setSelectedMode("All");
    setSortBy("default");
  };

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

    const matchesMode =
      selectedMode === "All" || program.mode_of_delivery === selectedMode;

    return matchesSearch && matchesCategory && matchesDuration && matchesMode;
  }).sort((a, b) => {
    switch (sortBy) {
      case "name_asc": return a.title.localeCompare(b.title);
      case "name_desc": return b.title.localeCompare(a.title);
      case "duration_asc": return parseInt(a.duration) - parseInt(b.duration);
      case "duration_desc": return parseInt(b.duration) - parseInt(a.duration);
      default: return 0;
    }
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
      {/* Header Section - hidden during enrollment flow */}
      {!userId && (
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
      )}

      {/* Back button - shown during enrollment flow */}
      {userId && (
        <div className="bg-white border-b border-gray-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <button
              onClick={() => router.back()}
              className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
            >
              <FiArrowLeft className="w-4 h-4" />
              Back to Course Selection
            </button>
          </div>
        </div>
      )}

      {/* Search and Filters - Sticky */}
      <section className="sticky top-0 z-20 bg-white/85 backdrop-blur-xl border-b border-gray-200/50 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
        {/* Yellow accent line */}
        <div className="h-0.5 bg-gradient-to-r from-yellow-400 via-yellow-300 to-transparent"></div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
          {/* Desktop layout */}
          <div className="flex items-center gap-3">
            {/* Search */}
            <div className="relative flex-1 lg:max-w-xs">
              <FiSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
              <input
                type="text"
                placeholder="Search programmes..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 focus:bg-white text-sm transition-all placeholder:text-gray-400"
              />
              {searchTerm && (
                <button
                  onClick={() => setSearchTerm("")}
                  className="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded-full bg-gray-200 text-gray-500 hover:bg-gray-300 hover:text-gray-700 transition-colors"
                >
                  <FiX className="w-3 h-3" />
                </button>
              )}
            </div>

            {/* Desktop: divider + filters */}
            <div className="hidden lg:flex items-center gap-2 flex-1">
              <div className="w-px h-6 bg-gray-200 mx-1"></div>

              {[
                { value: selectedCategory, setter: setSelectedCategory, icon: FiFilter, active: selectedCategory !== "All",
                  options: categories.map(c => ({ value: c, label: c })) },
                { value: selectedMode, setter: setSelectedMode, icon: FiGlobe, active: selectedMode !== "All",
                  options: deliveryModes.map(m => ({ value: m, label: m === "All" ? "All Modes" : m })) },
                { value: selectedDuration, setter: setSelectedDuration, icon: FiClock, active: selectedDuration !== "All",
                  options: [
                    { value: "All", label: "All Durations" },
                    { value: "Short", label: "Short ≤100 hrs" },
                    { value: "Medium", label: "Medium 101-200 hrs" },
                    { value: "Long", label: "Long 200+ hrs" },
                  ] },
                { value: sortBy, setter: setSortBy, icon: FiArrowUp, active: sortBy !== "default",
                  options: [
                    { value: "default", label: "Sort: Default" },
                    { value: "name_asc", label: "Name (A-Z)" },
                    { value: "name_desc", label: "Name (Z-A)" },
                    { value: "duration_asc", label: "Duration (Shortest)" },
                    { value: "duration_desc", label: "Duration (Longest)" },
                  ] },
              ].map((filter, idx) => (
                <div key={idx} className="relative group">
                  <select
                    value={filter.value}
                    onChange={(e) => filter.setter(e.target.value)}
                    className={`pl-8 pr-8 py-2 rounded-full text-sm cursor-pointer transition-all appearance-none ${
                      filter.active
                        ? "bg-yellow-400/20 text-yellow-800 font-medium border border-yellow-400/40 shadow-sm"
                        : "bg-gray-50 text-gray-600 border border-gray-200 hover:border-gray-300 hover:bg-gray-100"
                    }`}
                    disabled={isLoading}
                  >
                    {filter.options.map((opt) => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                  <filter.icon className={`absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none ${filter.active ? "text-yellow-600" : "text-gray-400"}`} />
                  <FiChevronDown className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 w-3 h-3 pointer-events-none" />
                </div>
              ))}

              {hasActiveFilters && (
                <button
                  onClick={clearAllFilters}
                  className="ml-1 px-2.5 py-1.5 text-xs text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors font-medium"
                >
                  Reset
                </button>
              )}
            </div>

            {/* Results count - desktop */}
            <div className="hidden lg:flex items-center gap-2 text-sm text-gray-500 whitespace-nowrap">
              {isLoading ? (
                <div className="h-4 w-20 bg-gray-200 rounded animate-pulse"></div>
              ) : (
                <span className="px-3 py-1 bg-gray-50 rounded-full border border-gray-100">
                  <span className="font-semibold text-gray-900">{filteredProgrammes.length}</span>
                  <span className="text-gray-400 mx-1">/</span>
                  {programmes.length}
                </span>
              )}
            </div>

            {/* Mobile filter toggle */}
            <button
              onClick={() => setShowFilters(!showFilters)}
              className={`lg:hidden relative flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all ${
                showFilters
                  ? "bg-gray-900 text-white shadow-lg"
                  : hasActiveFilters
                    ? "bg-yellow-400 text-gray-900 shadow-md"
                    : "bg-gray-50 text-gray-700 border border-gray-200 hover:border-gray-300"
              }`}
            >
              <FiSliders className="w-4 h-4" />
              <span className="hidden sm:inline">{showFilters ? "Close" : "Filters"}</span>
              {hasActiveFilters && !showFilters && (
                <span className="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-sm">
                  {[searchTerm, selectedCategory !== "All", selectedMode !== "All", selectedDuration !== "All", sortBy !== "default"].filter(Boolean).length}
                </span>
              )}
            </button>
          </div>

          {/* Active filter pills row - desktop & mobile (when panel closed) */}
          {hasActiveFilters && !isLoading && !showFilters && (
            <div className="flex items-center gap-1.5 mt-2.5 overflow-x-auto scrollbar-hide pb-0.5">
              {searchTerm && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-yellow-50 text-yellow-800 border border-yellow-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  &quot;{searchTerm}&quot;
                  <button onClick={() => setSearchTerm("")} className="p-0.5 rounded-full hover:bg-yellow-200/50 transition-colors"><FiX className="w-3 h-3" /></button>
                </span>
              )}
              {selectedCategory !== "All" && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-blue-50 text-blue-700 border border-blue-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  {selectedCategory}
                  <button onClick={() => setSelectedCategory("All")} className="p-0.5 rounded-full hover:bg-blue-200/50 transition-colors"><FiX className="w-3 h-3" /></button>
                </span>
              )}
              {selectedMode !== "All" && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-green-50 text-green-700 border border-green-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  {selectedMode}
                  <button onClick={() => setSelectedMode("All")} className="p-0.5 rounded-full hover:bg-green-200/50 transition-colors"><FiX className="w-3 h-3" /></button>
                </span>
              )}
              {selectedDuration !== "All" && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-purple-50 text-purple-700 border border-purple-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  {selectedDuration}
                  <button onClick={() => setSelectedDuration("All")} className="p-0.5 rounded-full hover:bg-purple-200/50 transition-colors"><FiX className="w-3 h-3" /></button>
                </span>
              )}
              {sortBy !== "default" && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-gray-100 text-gray-600 border border-gray-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  Sorted
                  <button onClick={() => setSortBy("default")} className="p-0.5 rounded-full hover:bg-gray-200/50 transition-colors"><FiX className="w-3 h-3" /></button>
                </span>
              )}
              <button onClick={clearAllFilters} className="lg:hidden text-xs text-red-500 font-medium whitespace-nowrap shrink-0 ml-1">
                Clear all
              </button>
            </div>
          )}
        </div>
      </section>

      {/* Mobile Bottom Sheet Filters */}
      <AnimatePresence>
        {showFilters && (
          <>
            {/* Backdrop */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.2 }}
              className="lg:hidden fixed inset-0 z-30 bg-black/40 backdrop-blur-sm"
              onClick={() => setShowFilters(false)}
            />
            {/* Sheet */}
            <motion.div
              initial={{ y: "100%" }}
              animate={{ y: 0 }}
              exit={{ y: "100%" }}
              transition={{ type: "spring", damping: 30, stiffness: 300 }}
              className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white rounded-t-3xl shadow-2xl max-h-[75vh] flex flex-col"
            >
              {/* Handle + Header - always visible */}
              <div className="shrink-0 px-5 pt-3 pb-3 border-b border-gray-100">
                <div className="flex justify-center mb-3">
                  <div className="w-10 h-1 bg-gray-300 rounded-full"></div>
                </div>
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-bold text-gray-900">Filters</h3>
                  <div className="flex items-center gap-3">
                    {hasActiveFilters && (
                      <button onClick={clearAllFilters} className="text-sm text-red-500 font-medium">
                        Reset all
                      </button>
                    )}
                    <button
                      onClick={() => setShowFilters(false)}
                      className="p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors"
                    >
                      <FiX className="w-4 h-4 text-gray-600" />
                    </button>
                  </div>
                </div>
              </div>

              {/* Scrollable filter groups */}
              <div className="px-5 py-4 overflow-y-auto flex-1 min-h-0">
                <div className="space-y-5">
                  {[
                    { label: "Category", icon: FiFilter, value: selectedCategory, setter: setSelectedCategory,
                      options: categories.map(c => ({ value: c, label: c })) },
                    { label: "Delivery Mode", icon: FiGlobe, value: selectedMode, setter: setSelectedMode,
                      options: deliveryModes.map(m => ({ value: m, label: m === "All" ? "All Modes" : m })) },
                    { label: "Duration", icon: FiClock, value: selectedDuration, setter: setSelectedDuration,
                      options: [
                        { value: "All", label: "All Durations" },
                        { value: "Short", label: "Short (≤100 hrs)" },
                        { value: "Medium", label: "Medium (101-200 hrs)" },
                        { value: "Long", label: "Long (200+ hrs)" },
                      ] },
                    { label: "Sort By", icon: FiArrowUp, value: sortBy, setter: setSortBy,
                      options: [
                        { value: "default", label: "Default" },
                        { value: "name_asc", label: "Name (A-Z)" },
                        { value: "name_desc", label: "Name (Z-A)" },
                        { value: "duration_asc", label: "Shortest first" },
                        { value: "duration_desc", label: "Longest first" },
                      ] },
                  ].map((group) => (
                    <div key={group.label}>
                      <div className="flex items-center gap-2 mb-2.5">
                        <group.icon className="w-4 h-4 text-gray-400" />
                        <span className="text-sm font-semibold text-gray-700">{group.label}</span>
                      </div>
                      <div className="flex flex-wrap gap-2">
                        {group.options.map((opt) => (
                          <button
                            key={opt.value}
                            onClick={() => group.setter(opt.value)}
                            className={`px-3 py-1.5 rounded-full text-sm transition-all ${
                              group.value === opt.value
                                ? "bg-gray-900 text-white font-medium shadow-md"
                                : "bg-gray-50 text-gray-600 border border-gray-200 hover:border-gray-300 hover:bg-gray-100"
                            }`}
                          >
                            {opt.label}
                          </button>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Sticky bottom button */}
              <div className="shrink-0 px-5 py-4 border-t border-gray-100 bg-white">
                <button
                  onClick={() => setShowFilters(false)}
                  className="w-full py-3 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold rounded-xl transition-colors shadow-sm"
                >
                  {isLoading ? "Loading..." : `Show ${filteredProgrammes.length} programme${filteredProgrammes.length !== 1 ? "s" : ""}`}
                </button>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>

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
                <p className="text-gray-600 mb-1">
                  No results
                  {searchTerm && <> matching &quot;<span className="font-medium">{searchTerm}</span>&quot;</>}
                  {selectedCategory !== "All" && <> in <span className="font-medium">{selectedCategory}</span></>}
                  {selectedMode !== "All" && <> with <span className="font-medium">{selectedMode}</span> delivery</>}
                  {selectedDuration !== "All" && <> for <span className="font-medium">{selectedDuration.toLowerCase()}</span> duration</>}
                </p>
                <button
                  onClick={clearAllFilters}
                  className="mt-3 text-sm text-yellow-600 hover:text-yellow-700 font-medium underline underline-offset-2"
                >
                  Clear all filters
                </button>
              </div>
            ) : (
              filteredProgrammes.map((programme, index) => (
                <motion.div
                  key={programme.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                >
                  <ProgrammeCard programme={programme} userId={userId} centreId={centreId} token={token} />
                </motion.div>
              ))
            )}
          </div>
        </div>
      </section>
    </div>
  );
} 