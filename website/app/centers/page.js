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
  getDistrictsByBranch,
  getCentresByDistrict,
  getTotalCentresCount,
  getAllRegionsWithCentreCounts,
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
        className={`w-full flex items-center gap-2 pl-10 pr-10 py-2.5 rounded-xl text-sm text-left transition-all ${
          disabled
            ? "bg-gray-100/50 border border-gray-200 text-gray-400 cursor-not-allowed"
            : selectedOption
            ? "bg-yellow-400/15 border border-yellow-400/40 text-yellow-800 font-medium"
            : isOpen
            ? "border border-yellow-400 ring-2 ring-yellow-400/20 bg-white"
            : "bg-gray-50 border border-gray-200 hover:border-gray-300 hover:bg-gray-100 text-gray-600"
        }`}
      >        {selectedOption ? `${selectedOption.title} (${selectedOption.total_centres || 0} ${selectedOption.total_centres === 1 ? 'centre' : 'centres'})` : placeholder}
      </button>
      <Icon className={`absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none ${selectedOption && !disabled ? "text-yellow-600" : "text-gray-400"}`} />
      <FiChevronDown
        className={`absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 w-3.5 h-3.5 pointer-events-none transition-transform ${
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
                    {`${option.title} (${option.total_centres || 0} ${option.total_centres === 1 ? 'centre' : 'centres'})`}
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
  const [totalCentres, setTotalCentres] = useState(0);
  const [districtCentresCount, setDistrictCentresCount] = useState(0);
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
        const [regionsData, totalCount] = await Promise.all([
          getAllRegionsWithCentreCounts(),
          getTotalCentresCount(),
        ]);
        setRegions(regionsData || []);
        setTotalCentres(totalCount || 0);
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
      const centresData = data?.centres || [];
      setCenters(centresData);
      setDistrictCentresCount(centresData.length);
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
              <span className="text-yellow-400">{totalCentres}</span> Training {totalCentres === 1 ? 'Centre' : 'Centres'}
              <br />
              <span className="text-yellow-400">Across Ghana</span>
            </h1>
            <p className="text-lg text-gray-400 leading-relaxed max-w-lg">
              Browse our network of training centres by region and district.
              Find the closest centre to start your coding journey.
            </p>
          </motion.div>
        </div>
      </section>

      {/* Region & District Selection - Sticky */}
      <section className="sticky top-0 z-20 bg-white/85 backdrop-blur-xl border-b border-gray-200/50 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
        <div className="h-0.5 bg-gradient-to-r from-yellow-400 via-yellow-300 to-transparent"></div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
          <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            {/* Region Select */}
            <div className="flex-1 min-w-0">
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

            {/* Divider - desktop only */}
            <div className="hidden sm:block w-px h-6 bg-gray-200 shrink-0"></div>

            {/* District Select */}
            <div className="flex-1 min-w-0">
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

            {/* Clear button */}
            {selectedRegion && (
              <button
                onClick={() => {
                  setSelectedRegion(null);
                  setSelectedDistrict(null);
                  setDistricts([]);
                  setCenters([]);
                  setSearchQuery("");
                  setImageErrors({});
                }}
                className="hidden sm:block shrink-0 px-2.5 py-1.5 text-xs text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors font-medium"
              >
                Reset
              </button>
            )}
          </div>

          {/* Breadcrumb pills */}
          {selectedRegion && (
            <div className="flex items-center gap-1.5 mt-2.5 overflow-x-auto scrollbar-hide pb-0.5">
              <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-yellow-50 text-yellow-800 border border-yellow-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                <FiMapPin className="w-3 h-3" />
                {selectedRegion.title}
                <button
                  onClick={() => {
                    setSelectedRegion(null);
                    setSelectedDistrict(null);
                    setDistricts([]);
                    setCenters([]);
                    setSearchQuery("");
                    setImageErrors({});
                  }}
                  className="p-0.5 rounded-full hover:bg-yellow-200/50 transition-colors"
                >
                  <FiX className="w-3 h-3" />
                </button>
              </span>
              {selectedDistrict && (
                <span className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-blue-50 text-blue-700 border border-blue-200/60 rounded-full text-xs font-medium whitespace-nowrap shrink-0">
                  <FiNavigation className="w-3 h-3" />
                  {selectedDistrict.title}
                  <button
                    onClick={() => {
                      setSelectedDistrict(null);
                      setCenters([]);
                      setSearchQuery("");
                      setImageErrors({});
                    }}
                    className="p-0.5 rounded-full hover:bg-blue-200/50 transition-colors"
                  >
                    <FiX className="w-3 h-3" />
                  </button>
                </span>
              )}
            </div>
          )}

          {/* Statistics Pills - District count */}
          {selectedDistrict && (
            <div className="flex items-center gap-3 mt-3 flex-wrap">
              <span className="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200/60 rounded-full text-xs font-medium">
                <span className="w-5 h-5 flex items-center justify-center bg-blue-400 text-white rounded-full text-xs font-bold">
                  {districtCentresCount}
                </span>
                {districtCentresCount === 1 ? 'Center' : 'Centers'} in {selectedDistrict.title}
              </span>
            </div>
          )}
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
              <div className="flex items-center gap-3">
                <h2 className="text-xl font-bold text-gray-900">Centers</h2>
                <span className="px-2.5 py-0.5 bg-gray-50 rounded-full border border-gray-100 text-sm text-gray-500">
                  <span className="font-semibold text-gray-900">{filteredCenters.length}</span>
                  <span className="text-gray-400 mx-1">/</span>
                  {centers.length}
                </span>
              </div>

              <div className="relative">
                <FiSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search centers..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10 pr-9 py-2.5 text-sm rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 focus:bg-white transition-all placeholder:text-gray-400 w-52"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery("")}
                    className="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded-full bg-gray-200 text-gray-500 hover:bg-gray-300 hover:text-gray-700 transition-colors"
                  >
                    <FiX className="w-3 h-3" />
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
