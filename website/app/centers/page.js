"use client";

import { useState, useEffect, useCallback, useRef, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { motion } from "framer-motion";
import Image from "next/image";
import {
  FiMapPin,
  FiSearch,
  FiX,
  FiChevronDown,
  FiNavigation,
  FiArrowRight,
} from "react-icons/fi";
import Link from "next/link";
import {
  getAllRegions,
  getDistrictsByBranch,
  getCentresByDistrict,
} from "../../services/pages";

function SearchableSelect({
  options,
  value,
  onChange,
  placeholder,
  disabled,
  icon: Icon,
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [query, setQuery] = useState("");
  const ref = useRef(null);

  const filtered = options.filter((o) =>
    o.title.toLowerCase().includes(query.toLowerCase())
  );

  const selectedOption = options.find((o) => o.id === value);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setIsOpen(false);
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => {
          if (!disabled) {
            setIsOpen(!isOpen);
            setQuery("");
          }
        }}
        className={`w-full flex items-center gap-2 pl-10 pr-10 py-3 border rounded-xl text-sm text-left transition-all ${
          disabled
            ? "bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed"
            : isOpen
            ? "border-yellow-400 ring-2 ring-yellow-100 bg-white"
            : "border-gray-300 bg-white hover:border-gray-400"
        }`}
      >
        {selectedOption ? selectedOption.title : placeholder}
      </button>
      <Icon className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 pointer-events-none" />
      <FiChevronDown
        className={`absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 pointer-events-none transition-transform ${
          isOpen ? "rotate-180" : ""
        }`}
      />

      {isOpen && (
        <div className="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
          <div className="p-2 border-b border-gray-100">
            <div className="relative">
              <FiSearch className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <input
                type="text"
                autoFocus
                placeholder="Type to filter..."
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                className="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-100 bg-gray-50"
              />
            </div>
          </div>
          <ul className="max-h-52 overflow-y-auto py-1">
            {filtered.length > 0 ? (
              filtered.map((option) => (
                <li key={option.id}>
                  <button
                    type="button"
                    onClick={() => {
                      onChange(option);
                      setIsOpen(false);
                      setQuery("");
                    }}
                    className={`w-full text-left px-4 py-2.5 text-sm transition-colors ${
                      option.id === value
                        ? "bg-yellow-50 text-yellow-700 font-medium"
                        : "text-gray-700 hover:bg-gray-50"
                    }`}
                  >
                    {option.title}
                  </button>
                </li>
              ))
            ) : (
              <li className="px-4 py-3 text-sm text-gray-400 text-center">
                No results found
              </li>
            )}
          </ul>
        </div>
      )}
    </div>
  );
}

export default function CentersPage() {
  return (
    <Suspense>
      <CentersPageContent />
    </Suspense>
  );
}

function CentersPageContent() {
  const searchParams = useSearchParams();
  const [regions, setRegions] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [centers, setCenters] = useState([]);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [selectedDistrict, setSelectedDistrict] = useState(null);
  const [loadingRegions, setLoadingRegions] = useState(true);
  const [loadingDistricts, setLoadingDistricts] = useState(false);
  const [loadingCenters, setLoadingCenters] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [error, setError] = useState(null);
  const [imageErrors, setImageErrors] = useState({});

  useEffect(() => {
    const fetchRegions = async () => {
      try {
        setLoadingRegions(true);
        const data = await getAllRegions();
        setRegions(data || []);
      } catch (err) {
        console.error("Error fetching regions:", err);
        setError("Failed to load regions. Please try again.");
      } finally {
        setLoadingRegions(false);
      }
    };
    fetchRegions();
  }, []);

  const handleRegionSelect = useCallback(async (region) => {
    setSelectedRegion(region);
    setSelectedDistrict(null);
    setDistricts([]);
    setCenters([]);
    setSearchQuery("");
    setImageErrors({});

    try {
      setLoadingDistricts(true);
      setError(null);
      const data = await getDistrictsByBranch(region.id);
      setDistricts(data?.districts || []);
    } catch (err) {
      console.error("Error fetching districts:", err);
      setError("Failed to load districts. Please try again.");
    } finally {
      setLoadingDistricts(false);
    }
  }, []);

  // Auto-select region from query param (e.g., /centers?region=Ashanti)
  useEffect(() => {
    const regionParam = searchParams.get("region");
    if (!regionParam || regions.length === 0 || selectedRegion) return;

    const normalize = (str) =>
      str.toLowerCase().replace(/\s*region\s*/g, "").trim();

    const match = regions.find(
      (r) => normalize(r.title) === normalize(regionParam)
    );
    if (match) handleRegionSelect(match);
  }, [regions, searchParams, selectedRegion, handleRegionSelect]);

  const handleDistrictSelect = useCallback(async (district) => {
    setSelectedDistrict(district);
    setCenters([]);
    setSearchQuery("");
    setImageErrors({});

    try {
      setLoadingCenters(true);
      setError(null);
      const data = await getCentresByDistrict(district.id);
      setCenters(data?.centres || []);
    } catch (err) {
      console.error("Error fetching centers:", err);
      setError("Failed to load centers. Please try again.");
    } finally {
      setLoadingCenters(false);
    }
  }, []);

  const filteredCenters = centers.filter((c) =>
    c.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const getFirstImage = (centre) => {
    if (Array.isArray(centre.images) && centre.images.length > 0) {
      return centre.images[0];
    }
    if (centre.image && centre.image.trim() !== "") {
      return centre.image;
    }
    return null;
  };

  const hasValidImage = (centre) =>
    getFirstImage(centre) && !imageErrors[centre.id];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-900 to-gray-800">
        <div className="absolute inset-0 opacity-[0.03]">
          <div
            className="absolute inset-0"
            style={{
              backgroundImage:
                "radial-gradient(circle at 1px 1px, white 1px, transparent 0)",
              backgroundSize: "40px 40px",
            }}
          />
        </div>
        <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-yellow-400 to-green-500" />

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="max-w-2xl"
          >
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-yellow-400/10 border border-yellow-400/20 mb-6">
              <FiNavigation className="w-3.5 h-3.5 text-yellow-400" />
              <span className="text-xs font-medium text-yellow-400 tracking-wide uppercase">
                Center Directory
              </span>
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-[1.1] mb-5">
              Training Centers
              <br />
              <span className="text-yellow-400">Across Ghana</span>
            </h1>
            <p className="text-lg text-gray-400 leading-relaxed max-w-lg">
              Browse our network of training centers by region and district.
              Find the closest center to start your coding journey.
            </p>
          </motion.div>
        </div>
      </section>

      {/* Region & District Selection */}
      <section className="py-8 bg-gradient-to-b from-gray-100 to-gray-50 border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Region Select */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Region
                </label>
                <SearchableSelect
                  options={regions}
                  value={selectedRegion?.id || ""}
                  onChange={handleRegionSelect}
                  placeholder={
                    loadingRegions ? "Loading regions..." : "Select a region"
                  }
                  disabled={loadingRegions}
                  icon={FiMapPin}
                />
              </div>

              {/* District Select */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  District
                </label>
                <SearchableSelect
                  options={districts}
                  value={selectedDistrict?.id || ""}
                  onChange={handleDistrictSelect}
                  placeholder={
                    !selectedRegion
                      ? "Select a region first"
                      : loadingDistricts
                      ? "Loading districts..."
                      : districts.length === 0
                      ? "No districts available"
                      : "Select a district"
                  }
                  disabled={!selectedRegion || loadingDistricts}
                  icon={FiNavigation}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Centers Content */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        {error && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl flex items-center justify-between"
          >
            <p className="text-red-700 text-sm font-medium">{error}</p>
            <button
              onClick={() => setError(null)}
              className="text-red-500 hover:text-red-700 transition-colors"
            >
              <FiX className="w-4 h-4" />
            </button>
          </motion.div>
        )}

        {/* Default state */}
        {!selectedRegion && !loadingRegions && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.4 }}
            className="flex flex-col items-center justify-center py-20 text-center"
          >
            <Image
              src="/images/one-million-coders-logo.png"
              alt="One Million Coders"
              width={180}
              height={60}
              className="mb-8 opacity-20"
            />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">
              Select a region to get started
            </h3>
            <p className="text-gray-500 text-sm max-w-sm">
              Choose a region above to browse districts and discover
              training centers near you.
            </p>
          </motion.div>
        )}

        {/* Region selected but no district */}
        {selectedRegion && !selectedDistrict && !loadingDistricts && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.4 }}
            className="flex flex-col items-center justify-center py-20 text-center"
          >
            <div className="w-20 h-20 rounded-2xl bg-gradient-to-br from-yellow-50 to-yellow-100 flex items-center justify-center mb-6 border border-yellow-200">
              <FiNavigation className="w-9 h-9 text-yellow-400" />
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-2">
              Now select a district
            </h3>
            <p className="text-gray-500 text-sm max-w-sm">
              Choose a district in{" "}
              <span className="font-medium text-gray-700">
                {selectedRegion.title}
              </span>{" "}
              to view available training centers.
            </p>
          </motion.div>
        )}

        {/* Loading states */}
        {(loadingDistricts || loadingCenters) && (
          <div className="py-20 text-center">
            <div className="inline-flex items-center gap-3 px-5 py-3 rounded-full bg-white border border-gray-200 shadow-sm">
              <div className="w-5 h-5 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin" />
              <span className="text-sm text-gray-500 font-medium">
                {loadingDistricts
                  ? "Loading districts..."
                  : "Loading centers..."}
              </span>
            </div>
          </div>
        )}

        {/* Centers view */}
        {selectedDistrict && !loadingCenters && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.3 }}
          >
            {/* Header bar */}
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-bold text-gray-900">
                {filteredCenters.length}{" "}
                {filteredCenters.length === 1 ? "Center" : "Centers"} found
              </h2>

              <div className="relative">
                <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search centers..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9 pr-8 py-2.5 text-sm rounded-xl border border-gray-200 bg-white focus:outline-none focus:border-yellow-400 focus:ring-2 focus:ring-yellow-100 transition-all w-52"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery("")}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <FiX className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>
            </div>

                {/* Center cards */}
                {filteredCenters.length > 0 ? (
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {filteredCenters.map((centre, index) => (
                      <motion.div
                        key={centre.id}
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                          duration: 0.35,
                          delay: Math.min(index * 0.06, 0.3),
                          ease: "easeOut",
                        }}
                      >
                        <Link
                          href={`/centers/${centre.id}?district_id=${selectedDistrict.id}&region=${encodeURIComponent(selectedRegion.title)}&district=${encodeURIComponent(selectedDistrict.title)}`}
                          className="block bg-white rounded-xl border border-gray-200 hover:border-yellow-300 hover:shadow-lg transition-all duration-300 overflow-hidden group"
                        >
                          {/* Card image */}
                          <div className="relative h-36 bg-gray-100 overflow-hidden">
                            {hasValidImage(centre) ? (
                              <Image
                                src={getFirstImage(centre)}
                                alt={centre.title}
                                fill
                                className="object-cover group-hover:scale-105 transition-transform duration-500"
                                onError={() =>
                                  setImageErrors((prev) => ({
                                    ...prev,
                                    [centre.id]: true,
                                  }))
                                }
                              />
                            ) : (
                              <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                                <Image
                                  src="/images/one-million-coders-logo.png"
                                  alt="One Million Coders"
                                  width={120}
                                  height={40}
                                  className="opacity-15"
                                />
                              </div>
                            )}
                            {centre.is_pwd_friendly && (
                              <span className="absolute top-2.5 right-2.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/90 backdrop-blur-sm text-[10px] font-semibold text-purple-600 shadow-sm">
                                &#9855; PWD Friendly
                              </span>
                            )}
                          </div>

                          {/* Card body */}
                          <div className="p-4">
                            <h3 className="font-semibold text-gray-900 text-sm leading-snug mb-2 line-clamp-2">
                              {centre.title}
                            </h3>
                            <div className="flex items-center gap-1.5 text-xs text-gray-500 mb-3">
                              <FiMapPin className="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" />
                              <span className="truncate">
                                {selectedDistrict.title},{" "}
                                {selectedRegion.title}
                              </span>
                            </div>
                            <div className="flex items-center justify-between">
                              <span className="text-xs text-yellow-600 font-medium group-hover:text-yellow-700 transition-colors">
                                View details
                              </span>
                              <FiArrowRight className="w-3.5 h-3.5 text-gray-400 group-hover:text-yellow-600 group-hover:translate-x-0.5 transition-all" />
                            </div>
                          </div>
                        </Link>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-16 bg-white rounded-2xl border border-gray-200">
                    <FiSearch className="w-8 h-8 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500 text-sm font-medium">
                      {searchQuery
                        ? `No centers match "${searchQuery}"`
                        : "No centers found in this district."}
                    </p>
                    {searchQuery && (
                      <button
                        onClick={() => setSearchQuery("")}
                        className="text-yellow-600 text-sm font-semibold hover:text-yellow-700 mt-2"
                      >
                        Clear search
                      </button>
                    )}
                  </div>
                )}
              </motion.div>
            )}
      </section>
    </div>
  );
}
