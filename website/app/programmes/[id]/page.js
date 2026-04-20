'use client';

import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useParams, useRouter, useSearchParams, usePathname } from 'next/navigation';
import Image from 'next/image';
import {
  FiArrowLeft,
  FiClock,
  FiBookOpen,
  FiAward,
  FiUsers,
  FiCheckCircle,
  FiTarget,
  FiGlobe,
  FiPlay,
  FiStar,
  FiLoader,
  FiMonitor,
  FiMapPin,
  FiNavigation,
  FiCalendar,
  FiChevronDown,
  FiX,
} from 'react-icons/fi';
import {
  getProgrammeData,
  getAllRegionsWithCentreCounts,
  getDistrictsByBranch,
  getProgrammeAvailabilityPerCentre,
} from '../../../services/pages';
import Button from '../../../components/Button';
import ProgrammeDetailsSkeleton from '@/components/ProgrammeDetailsSkeleton';
import RegistrationDialog from '@/components/RegistrationDialog';
import SearchableSelect from '@/components/SearchableSelect';

export default function CourseDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const userId = searchParams.get('user_id');
  const courseId = searchParams.get('course_id');
  const [imageError, setImageError] = useState(false);
  const centreId = searchParams.get('centre_id');
  const [activeTab, setActiveTab] = useState('overview');
  const [programme, setProgramme] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showRegistrationDialog, setShowRegistrationDialog] = useState(false);

  // Centre availability state
  const [regions, setRegions] = useState([]);
  const [districts, setDistricts] = useState([]);
  const [selectedRegion, setSelectedRegion] = useState(null);
  const [selectedDistrict, setSelectedDistrict] = useState(null);
  const [loadingRegions, setLoadingRegions] = useState(true);
  const [loadingDistricts, setLoadingDistricts] = useState(false);
  const [availabilityCentres, setAvailabilityCentres] = useState([]);
  const [availabilityMeta, setAvailabilityMeta] = useState(null);
  const [loadingAvailability, setLoadingAvailability] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [availabilityError, setAvailabilityError] = useState(null);
  const [expandedCentres, setExpandedCentres] = useState({});

  // Tab bar horizontal scroll (mobile): center the active tab inside its
  // container only when activeTab actually changes. Do NOT use scrollIntoView —
  // that scrolls the whole page vertically as a side effect.
  const tabNavRef = useRef(null);
  const tabRefs = useRef({});
  useEffect(() => {
    const nav = tabNavRef.current;
    const btn = tabRefs.current[activeTab];
    if (!nav || !btn) return;
    const target = btn.offsetLeft - (nav.clientWidth - btn.offsetWidth) / 2;
    nav.scrollTo({ left: Math.max(0, target), behavior: 'smooth' });
  }, [activeTab]);

  // Fetch programme data from API
  useEffect(() => {
    const fetchProgrammeData = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await getProgrammeData(params.id);
        setProgramme(data);
      } catch (err) {
        console.error('Error fetching programme:', err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    if (params.id) {
      fetchProgrammeData();
    }
  }, [params.id]);

  // Load regions for the availability section
  useEffect(() => {
    let cancelled = false;
    const fetchRegions = async () => {
      try {
        setLoadingRegions(true);
        const data = await getAllRegionsWithCentreCounts();
        if (!cancelled) setRegions(data || []);
      } catch (err) {
        console.error('Error fetching regions:', err);
      } finally {
        if (!cancelled) setLoadingRegions(false);
      }
    };
    fetchRegions();
    return () => {
      cancelled = true;
    };
  }, []);

  // Sync selection to URL (preserves existing params).
  // Use history.replaceState rather than router.replace to avoid Next.js app-router
  // scroll-to-top behaviour and re-renders when only a query param changes.
  const updateQuery = useCallback(
    (updates) => {
      if (typeof window === 'undefined') return;
      const next = new URLSearchParams(window.location.search);
      Object.entries(updates).forEach(([k, v]) => {
        if (v == null || v === '') next.delete(k);
        else next.set(k, String(v));
      });
      const qs = next.toString();
      const url = qs ? `${pathname}?${qs}` : pathname;
      window.history.replaceState(null, '', url);
    },
    [pathname]
  );

  const fetchAvailability = useCallback(
    async (districtId, page = 1, append = false) => {
      if (!params.id || !districtId) return;
      try {
        if (append) setLoadingMore(true);
        else setLoadingAvailability(true);
        setAvailabilityError(null);
        const response = await getProgrammeAvailabilityPerCentre(
          params.id,
          districtId,
          { page }
        );
        // Normalize response: supports { available_centres: [...] } (current API),
        // { data: [...] }, or Laravel paginator { data: { data: [...], ...meta } }
        const raw = response?.available_centres ?? response?.data;
        const centres = Array.isArray(raw)
          ? raw
          : Array.isArray(raw?.data)
          ? raw.data
          : Array.isArray(response?.centres)
          ? response.centres
          : [];
        const metaSource =
          response?.meta ??
          (!Array.isArray(raw) && raw && raw.current_page != null ? raw : null) ??
          response?.pagination;
        const meta = metaSource
          ? {
              current_page: metaSource.current_page ?? page,
              last_page: metaSource.last_page ?? null,
              per_page: metaSource.per_page ?? null,
              total: metaSource.total ?? null,
              next_page_url: metaSource.next_page_url ?? null,
            }
          : null;

        setAvailabilityCentres((prev) => (append ? [...prev, ...centres] : centres));
        setAvailabilityMeta(meta);
      } catch (err) {
        console.error('Error fetching availability:', err);
        setAvailabilityError('Failed to load centres. Please try again.');
      } finally {
        setLoadingAvailability(false);
        setLoadingMore(false);
      }
    },
    [params.id]
  );

  const handleRegionSelect = useCallback(
    async (region) => {
      setSelectedRegion(region);
      setSelectedDistrict(null);
      setDistricts([]);
      setAvailabilityCentres([]);
      setAvailabilityMeta(null);
      setExpandedCentres({});
      updateQuery({ region_id: region?.id, district_id: null });

      try {
        setLoadingDistricts(true);
        const data = await getDistrictsByBranch(region.id);
        setDistricts(data?.districts || []);
      } catch (err) {
        console.error('Error fetching districts:', err);
        setAvailabilityError('Failed to load districts. Please try again.');
      } finally {
        setLoadingDistricts(false);
      }
    },
    [updateQuery]
  );

  const handleDistrictSelect = useCallback(
    (district) => {
      setSelectedDistrict(district);
      setAvailabilityCentres([]);
      setAvailabilityMeta(null);
      setExpandedCentres({});
      updateQuery({ district_id: district?.id });
      fetchAvailability(district.id, 1, false);
    },
    [fetchAvailability, updateQuery]
  );

  const handleLoadMore = useCallback(() => {
    if (!selectedDistrict || !availabilityMeta) return;
    const nextPage = (availabilityMeta.current_page || 1) + 1;
    fetchAvailability(selectedDistrict.id, nextPage, true);
  }, [selectedDistrict, availabilityMeta, fetchAvailability]);

  const handleResetLocation = useCallback(() => {
    setSelectedRegion(null);
    setSelectedDistrict(null);
    setDistricts([]);
    setAvailabilityCentres([]);
    setAvailabilityMeta(null);
    setExpandedCentres({});
    updateQuery({ region_id: null, district_id: null });
  }, [updateQuery]);

  // Auto-select from query params on load
  useEffect(() => {
    const regionIdParam = searchParams.get('region_id');
    if (!regionIdParam || regions.length === 0 || selectedRegion) return;
    const match = regions.find((r) => String(r.id) === String(regionIdParam));
    if (match) handleRegionSelect(match);
  }, [regions, searchParams, selectedRegion, handleRegionSelect]);

  useEffect(() => {
    const districtIdParam = searchParams.get('district_id');
    if (!districtIdParam || districts.length === 0 || selectedDistrict) return;
    const match = districts.find((d) => String(d.id) === String(districtIdParam));
    if (match) handleDistrictSelect(match);
  }, [districts, searchParams, selectedDistrict, handleDistrictSelect]);

  const isAvailable = programme ? programme.status : false;

  // Loading state
  if (loading) {
    return <ProgrammeDetailsSkeleton />;
  }

  // Error state
  if (error || !programme) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            {error ? 'Error Loading Programme' : 'Programme Not Found'}
          </h1>
          {error && <p className="text-red-600 mb-4">{error}</p>}
          <Button onClick={() => router.push('/programmes')} icon={FiArrowLeft}>
            Back to Programmes
          </Button>
        </div>
      </div>
    );
  }

  const getCategoryColor = (categoryTitle) => {
    const colors = {
      'Cybersecurity': 'from-red-400 to-rose-500',
      'DATA Protection': 'from-blue-500 to-blue-600',
      'Data Protection': 'from-blue-500 to-blue-600', // Alternative naming
      'Artificial Intelligence Training': 'from-purple-500 to-purple-600',
      'Mobile Application Development': 'from-green-500 to-green-600',
      'Systems Administration': 'from-orange-500 to-orange-600',
      'Web Application Programming': 'from-indigo-500 to-indigo-600',
      'BPO Training': 'from-pink-500 to-pink-600',
      'Other Special Training Programs': 'from-gray-500 to-gray-600'
    };
    return colors[categoryTitle] || 'from-gray-500 to-gray-600';
  };

  const tabs = [
    { id: 'overview', label: 'Overview', icon: FiBookOpen },
    { id: 'curriculum', label: 'Curriculum', icon: FiTarget },
    { id: 'certifications', label: 'Certifications', icon: FiAward },
    { id: 'prerequisites', label: 'Prerequisites', icon: FiCheckCircle }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className={`relative py-20 bg-gradient-to-br ${getCategoryColor(programme.category?.title)} overflow-hidden`}>
        {/* Background Pattern */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute inset-0" style={{
            backgroundImage: `radial-gradient(circle at 1px 1px, white 1px, transparent 0)`,
            backgroundSize: '20px 20px'
          }}></div>
        </div>

        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button */}
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.5 }}
            className="mb-8"
          >
            <Button
              onClick={() => router.push('/programmes')}
              variant="ghost"
              icon={FiArrowLeft}
              iconPosition="left"
              className="!text-white !border-white/30 hover:!bg-white/10"
            >
              Back to Programmes
            </Button>
          </motion.div>

          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Programme Info */}
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              className="text-white"
            >
              {/* Availability Badge */}
              <div className="flex items-center space-x-3 mb-6">
                <span className="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                  {programme.category?.title}
                </span>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${isAvailable
                  ? 'bg-green-500/20 text-green-100 border border-green-400/30'
                  : 'bg-orange-500/20 text-orange-100 border border-orange-400/30'
                  }`}>
                  {isAvailable ? 'Available Now' : 'Coming Soon'}
                </span>
              </div>

              <h1 className="text-4xl lg:text-5xl font-bold mb-6 leading-tight">
                {programme.title}
              </h1>

              {programme.sub_title && (
                <p className="text-xl text-white/90 mb-6 font-medium">
                  {programme.sub_title}
                </p>
              )}

              <p className="text-lg text-white/80 mb-8 leading-relaxed">
                {programme.job_responsible || programme.description || 'Professional training program designed to advance your career.'}
              </p>

              {/* Quick Stats */}
              <div className="grid grid-cols-2 gap-6 mb-8">
                {programme.duration && (
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                      <FiClock className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="font-semibold">{programme.duration}</div>
                      <div className="text-white/70 text-sm">Duration</div>
                    </div>
                  </div>
                )}

                {programme.course_modules_count > 0 && (
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                      <FiBookOpen className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="font-semibold">{programme.course_modules_count} Modules</div>
                      <div className="text-white/70 text-sm">Curriculum</div>
                    </div>
                  </div>
                )}
              </div>

              {/* CTA Buttons */}
              <div className="flex flex-wrap gap-3">
                <Button
                  onClick={() => {
                    if (userId) {
                      isAvailable && setShowRegistrationDialog(true);
                    } else {
                      router.push('/register');
                    }
                  }}
                  variant="primary"
                  icon={FiPlay}
                  disabled={!isAvailable}
                  className="!bg-white !text-gray-900 hover:!bg-gray-100"
                >
                  {isAvailable ? (userId ? 'Enroll Now' : 'Get Started') : 'Notify When Available'}
                </Button>
                <Button
                  onClick={() => {
                    document
                      .getElementById('available-centres')
                      ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                  }}
                  variant="ghost"
                  icon={FiMapPin}
                  iconPosition="left"
                  className="!bg-white/15 !backdrop-blur-sm !text-white !border !border-white/50 hover:!bg-white/25 hover:!border-white/70 !shadow-sm"
                >
                  View training centres
                </Button>
              </div>
            </motion.div>

            {/* Programme Image */}
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="relative"
            >
              <div className="relative h-96 lg:h-[500px] rounded-2xl overflow-hidden shadow-2xl bg-gray-100">
                {programme.image && !imageError ? (
                  <>
                    <Image
                      src={programme.image}
                      alt={programme.title}
                      fill
                      className="object-cover"
                      onError={() => setImageError(true)}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                  </>
                ) : (
                  <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                    <Image
                      src="/images/one-million-coders-logo.png"
                      alt="One Million Coders"
                      width={180}
                      height={60}
                      className="opacity-15"
                    />
                  </div>
                )}
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Content Section */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Tab Navigation */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="mb-8 md:mb-12"
          >
            <div className="border-b border-gray-200 overflow-hidden">
              {/* Mobile: Horizontal scroll, Desktop: Flex */}
              <nav ref={tabNavRef} className="flex md:justify-start overflow-x-auto scrollbar-hide -mb-px">
                <div className="flex space-x-1 md:space-x-8 px-4 md:px-0 min-w-max md:min-w-0">
                  {tabs.map((tab) => (
                    <button
                      key={tab.id}
                      ref={(el) => {
                        tabRefs.current[tab.id] = el;
                      }}
                      onClick={() => setActiveTab(tab.id)}
                      className={`flex items-center space-x-1.5 md:space-x-2 py-3 md:py-4 px-3 md:px-1 border-b-2 font-medium text-xs md:text-sm transition-colors duration-200 whitespace-nowrap ${activeTab === tab.id
                        ? 'border-yellow-400 text-yellow-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                      <tab.icon className="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" />
                      <span className="hidden sm:inline md:inline">{tab.label}</span>
                      {/* Mobile: Show abbreviated labels */}
                      <span className="sm:hidden md:hidden">
                        {tab.label.split(' ')[0]}
                      </span>
                    </button>
                  ))}
                </div>
              </nav>
            </div>

            {/* Mobile: Show current tab name */}
            {/* <div className="block md:hidden mt-4 px-4">
              <div className="flex items-center space-x-2 text-sm font-medium text-gray-900">
                {tabs.find(tab => tab.id === activeTab)?.icon && (
                  React.createElement(tabs.find(tab => tab.id === activeTab).icon, {
                    className: "w-4 h-4 text-yellow-600"
                  })
                )}
                <span>{tabs.find(tab => tab.id === activeTab)?.label}</span>
              </div>
            </div> */}
          </motion.div>

          {/* Tab Content */}
          <motion.div
            key={activeTab}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
          >
            {activeTab === 'overview' && (
              <div className="grid lg:grid-cols-3 gap-6 lg:gap-12 px-4 md:px-0">
                <div className="lg:col-span-2">
                  <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Programme Overview</h2>
                  <div className="prose prose-lg max-w-none">
                    <p className="text-gray-600 leading-relaxed mb-4 md:mb-6 text-sm md:text-base">
                      {programme.job_responsible || programme.description || 'Professional training program designed to advance your career and provide industry-relevant skills.'}
                    </p>

                    <h3 className="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">What You&apos;ll Learn</h3>
                    <ul className="space-y-3">
                      {programme.overview?.what_you_will_learn && programme.overview.what_you_will_learn.length > 0 ? (
                        programme.overview.what_you_will_learn.map((item, index) => (
                          <li key={index} className="flex items-start space-x-3">
                            <FiCheckCircle className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                            <span className="text-gray-700">{item}</span>
                          </li>
                        ))
                      ) : (
                        programme.course_modules?.slice(0, 4).map((module, index) => (
                          <li key={index} className="flex items-start space-x-3">
                            <FiCheckCircle className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                            <span className="text-gray-700">{module.title}</span>
                          </li>
                        ))
                      )}
                    </ul>
                  </div>
                </div>

                <div className="space-y-4 md:space-y-6 lg:mt-0">
                  <div className="bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-200">
                    <h3 className="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">Programme Details</h3>
                    <div className="space-y-3">
                      {programme.duration && (
                        <div className="flex justify-between items-center py-2 border-b border-gray-100">
                          <span className="text-sm text-gray-500">Duration</span>
                          <span className="text-sm font-medium text-gray-900">{programme.duration}</span>
                        </div>
                      )}
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-500">Modules</span>
                        <span className="text-sm font-medium text-gray-900">{programme.course_modules_count || 0}</span>
                      </div>
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-500">Category</span>
                        <span className="text-sm font-medium text-gray-900 text-right max-w-[60%]">{programme.category?.title}</span>
                      </div>
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-500">Level</span>
                        <span className="text-sm font-medium text-gray-900">{programme.level || 'Professional'}</span>
                      </div>
                      {programme.mode_of_delivery && (
                        <div className="flex justify-between items-center py-2">
                          <span className="text-sm text-gray-500">Mode</span>
                          <span className={`text-sm font-medium flex items-center gap-1.5 ${programme.mode_of_delivery === "Online" ? "text-purple-700" :
                            programme.mode_of_delivery === "In Person" ? "text-red-700" : "text-blue-700"
                            }`}>
                            {programme.mode_of_delivery === "Online" ? (
                              <FiMonitor className="w-3.5 h-3.5" />
                            ) : programme.mode_of_delivery === "In Person" ? (
                              <FiMapPin className="w-3.5 h-3.5" />
                            ) : (
                              <FiGlobe className="w-3.5 h-3.5" />
                            )}
                            {programme.mode_of_delivery}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-4 md:p-6 border border-yellow-200">
                    <div className="flex items-center space-x-2 mb-2 md:mb-3">
                      <FiStar className="w-4 h-4 md:w-5 md:h-5 text-yellow-600 flex-shrink-0" />
                      <h3 className="text-base md:text-lg font-semibold text-gray-900">Skills and tools you&apos;ll learn</h3>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {programme.overview?.why_choose_this_course && programme.overview.why_choose_this_course.length > 0 ? (
                        programme.overview.why_choose_this_course.map((reason, index) => (
                          <span key={index} className="inline-block px-3 py-1.5 text-xs md:text-sm text-gray-700 bg-white border border-gray-200 rounded-full">
                            {reason}
                          </span>
                        ))
                      ) : (
                        <>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'curriculum' && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Programme Curriculum</h2>
                {programme.course_modules && programme.course_modules.length > 0 ? (
                  <div className="space-y-3 md:space-y-4">
                    {programme.course_modules.map((module, index) => (
                      <motion.div
                        key={module.id}
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.4, delay: index * 0.1 }}
                        className="bg-white rounded-xl p-4 md:p-6 shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-300"
                      >
                        <div className="flex items-center space-x-3 md:space-x-4">
                          <div className="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center text-white font-bold text-sm md:text-base flex-shrink-0">
                            {index + 1}
                          </div>
                          <div className="flex-1 min-w-0">
                            <h3 className="text-base md:text-lg font-semibold text-gray-900">{module.title}</h3>
                            {module.description && (
                              <p className="text-gray-600 mt-2 text-sm md:text-base">{module.description}</p>
                            )}
                          </div>
                        </div>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-12">
                    <FiBookOpen className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-600">Curriculum details will be available soon.</p>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'certifications' && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Certifications</h2>
                {programme.course_certification && programme.course_certification.length > 0 ? (
                  <div className="grid md:grid-cols-2 gap-4 md:gap-6">
                    {programme.course_certification.map((cert, index) => (
                      <motion.div
                        key={cert.id}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4, delay: index * 0.1 }}
                        className="bg-white rounded-xl p-4 md:p-6 shadow-md border border-gray-200"
                      >
                        <div className="flex items-center space-x-3 md:space-x-4 mb-3 md:mb-4">
                          <div className="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <FiAward className="w-5 h-5 md:w-6 md:h-6 text-white" />
                          </div>
                          <div className="min-w-0 flex-1">
                            <h3 className="text-base md:text-lg font-semibold text-gray-900">{cert.title}</h3>
                            <p className="text-xs md:text-sm text-gray-600 truncate">{cert.type || 'International Certification'}</p>
                          </div>
                        </div>
                        <p className="text-gray-700 text-xs md:text-sm leading-relaxed">
                          {cert.description || 'Industry-recognized certification that validates your skills and expertise.'}
                        </p>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-12">
                    <FiAward className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-600">Certification details will be available soon.</p>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'prerequisites' && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Prerequisites</h2>
                <div className="bg-white rounded-xl p-4 md:p-8 shadow-md border border-gray-200">
                  <div className="flex items-start space-x-3 md:space-x-4">
                    <div className="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                      <FiCheckCircle className="w-5 h-5 md:w-6 md:h-6 text-white" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Entry Requirements</h3>
                      <div className="text-gray-700 leading-relaxed text-sm md:text-base">
                        {programme.prerequisites ? (
                          <p>
                            {programme.prerequisites
                              .replace(/<[^>]*>/g, '') // Strip HTML tags
                              .replace(/\s+/g, ' ') // Normalize whitespace
                              .trim() || 'No specific prerequisites required. This programme is designed for beginners and professionals looking to advance their skills.'}
                          </p>
                        ) : (
                          <p>No specific prerequisites required. This programme is designed for beginners and professionals looking to advance their skills.</p>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </motion.div>
        </div>
      </section>

      {/* Available Centres Section */}
      <section
        id="available-centres"
        className="pb-20 md:pb-24 bg-gray-50 border-t border-gray-100 scroll-mt-4"
      >
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 md:pt-16">
          <div className="mb-8 md:mb-10">
            <p className="text-[11px] tracking-[0.18em] uppercase text-gray-400 font-medium mb-2">
              Find a centre
            </p>
            <h2 className="text-[22px] sm:text-2xl md:text-[28px] font-semibold text-gray-900 tracking-tight">
              Centres offering this programme
            </h2>
            <p className="text-sm text-gray-500 mt-1.5 max-w-2xl">
              Choose your region and district to see centres, cohorts, and session schedules.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 sm:gap-3 items-center mb-6">
            <SearchableSelect
              options={regions}
              value={selectedRegion?.id || ''}
              onChange={handleRegionSelect}
              placeholder={loadingRegions ? 'Loading regions…' : 'Region'}
              disabled={loadingRegions}
              icon={FiMapPin}
            />
            <SearchableSelect
              options={districts}
              value={selectedDistrict?.id || ''}
              onChange={handleDistrictSelect}
              placeholder={
                !selectedRegion
                  ? 'District'
                  : loadingDistricts
                  ? 'Loading…'
                  : districts.length === 0
                  ? 'No districts available'
                  : 'District'
              }
              disabled={!selectedRegion || loadingDistricts}
              icon={FiNavigation}
              formatOption={(o) => o.title}
            />
            <button
              onClick={handleResetLocation}
              disabled={!selectedRegion}
              className={`justify-self-start sm:justify-self-auto text-xs font-medium px-3 py-2 rounded-lg transition-colors ${
                selectedRegion
                  ? 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'
                  : 'text-gray-300 cursor-default'
              }`}
            >
              Reset
            </button>
          </div>

          {availabilityError && (
            <div className="mb-6 px-4 py-3 bg-red-50 border border-red-100 rounded-lg flex items-center justify-between">
              <p className="text-red-700 text-sm">{availabilityError}</p>
              <button
                onClick={() => setAvailabilityError(null)}
                className="text-red-400 hover:text-red-600"
                aria-label="Dismiss"
              >
                <FiX className="w-4 h-4" />
              </button>
            </div>
          )}

          {!selectedRegion && !loadingRegions && (
            <p className="text-sm text-gray-400 py-10">
              Choose a region to see available districts.
            </p>
          )}

          {selectedRegion && !selectedDistrict && !loadingDistricts && (
            <p className="text-sm text-gray-400 py-10">
              Now pick a district in{' '}
              <span className="text-gray-600">{selectedRegion.title}</span>.
            </p>
          )}

          {(loadingDistricts || loadingAvailability) && (
            <div className="flex items-center gap-2.5 text-sm text-gray-400 py-10">
              <div className="w-3.5 h-3.5 border-2 border-gray-300 border-t-yellow-400 rounded-full animate-spin" />
              {loadingDistricts ? 'Loading districts…' : 'Loading centres…'}
            </div>
          )}

          {selectedDistrict && !loadingAvailability && (
            <CentreAvailabilityList
              centres={availabilityCentres}
              meta={availabilityMeta}
              loadingMore={loadingMore}
              onLoadMore={handleLoadMore}
              expanded={expandedCentres}
              onToggleExpand={(id) =>
                setExpandedCentres((prev) => ({ ...prev, [id]: !prev[id] }))
              }
            />
          )}
        </div>
      </section>

      {/* Registration Dialog */}
      <RegistrationDialog
        isOpen={showRegistrationDialog}
        onClose={() => setShowRegistrationDialog(false)}
        programme={programme}
        userId={userId}
        courseId={courseId}
        centreId={centreId}
      />
    </div>
  );
}

// ──────────────────────────────────────────────
// Centre availability helpers & components
// ──────────────────────────────────────────────

function formatDate(value) {
  if (!value) return null;
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatTime(value) {
  if (!value) return null;
  // Accepts "HH:MM" or "HH:MM:SS" or ISO strings; returns null if not parseable as a time
  const timeMatch = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(value));
  if (timeMatch) {
    const [, hh, mm] = timeMatch;
    const d = new Date();
    d.setHours(Number(hh), Number(mm), 0, 0);
    return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
  }
  // Only try ISO parsing if the string looks like a full datetime; avoid turning
  // already-formatted strings like "8AM - 9:45AM" into NaN/garbage
  if (/\d{4}-\d{2}-\d{2}T/.test(String(value))) {
    const d = new Date(value);
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }
  }
  return null;
}

function getCentreId(centre, index) {
  return (
    centre?.id ??
    (centre?.centre_name ? `${centre.district_name ?? ''}:${centre.centre_name}` : null) ??
    `idx-${index}`
  );
}

function getCentreTitle(centre) {
  return centre?.title || centre?.centre_name || centre?.name || 'Centre';
}

function getCohortLabel(cohort) {
  return (
    cohort?.title ||
    cohort?.name ||
    cohort?.cohort_name ||
    cohort?.batch ||
    'Cohort'
  );
}

function getCohortSessions(cohort) {
  return (
    cohort?.sessions ||
    cohort?.course_sessions ||
    cohort?.schedule ||
    []
  );
}

function getSessionLabel(session) {
  return (
    session?.session_name ||
    session?.day ||
    session?.day_of_week ||
    session?.weekday ||
    session?.name ||
    ''
  );
}

function getSessionTimeLabel(session) {
  // If API already provides a formatted range string, use it verbatim
  if (session?.time && typeof session.time === 'string') return session.time;
  const start = session?.start_time || session?.starts_at || session?.start;
  const end = session?.end_time || session?.ends_at || session?.end;
  const s = formatTime(start);
  const e = formatTime(end);
  if (s && e) return `${s} – ${e}`;
  return s || e || '';
}

function getSessionRemaining(session) {
  return session?.remaining ?? session?.available_seats ?? session?.seats_available ?? null;
}

function getCentreLocation(centre) {
  const district =
    centre?.district?.title || centre?.district || centre?.district_name;
  const region =
    centre?.region?.title ||
    centre?.region ||
    centre?.branch?.title ||
    centre?.branch_name;
  return [district, region].filter(Boolean).join(', ');
}

function getCentreCapacity(centre) {
  return centre?.capacity ?? null;
}

function CentreAvailabilityList({
  centres,
  meta,
  loadingMore,
  onLoadMore,
  expanded,
  onToggleExpand,
}) {
  if (!centres || centres.length === 0) {
    return (
      <p className="text-sm text-gray-400 py-10">
        No centres offer this programme here yet. Try a different district.
      </p>
    );
  }

  const hasMore =
    meta?.next_page_url ||
    (meta?.current_page != null &&
      meta?.last_page != null &&
      meta.current_page < meta.last_page);

  const total = meta?.total ?? centres.length;

  return (
    <>
      <div className="flex items-baseline justify-between mb-3">
        <p className="text-xs text-gray-400 font-medium">
          {total} {total === 1 ? 'centre' : 'centres'}
        </p>
      </div>

      <div className="divide-y divide-gray-100 border-y border-gray-100 bg-white rounded-lg">
        {centres.map((centre, index) => {
          const cid = getCentreId(centre, index);
          return (
            <CentreAvailabilityRow
              key={cid}
              centre={centre}
              expanded={!!expanded[cid]}
              onToggle={() => onToggleExpand(cid)}
            />
          );
        })}
      </div>

      {hasMore && (
        <div className="mt-5 flex justify-center">
          <button
            onClick={onLoadMore}
            disabled={loadingMore}
            className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {loadingMore ? (
              <>
                <div className="w-3.5 h-3.5 border-2 border-gray-300 border-t-yellow-400 rounded-full animate-spin" />
                Loading…
              </>
            ) : (
              <>
                Load more
                <FiChevronDown className="w-4 h-4" />
              </>
            )}
          </button>
        </div>
      )}
    </>
  );
}

function formatDateRange(start, end) {
  if (!start && !end) return null;
  const s = start ? new Date(start) : null;
  const e = end ? new Date(end) : null;
  const valid = (d) => d && !Number.isNaN(d.getTime());
  if (valid(s) && valid(e)) {
    const sameYear = s.getFullYear() === e.getFullYear();
    const sameMonth = sameYear && s.getMonth() === e.getMonth();
    if (sameMonth) {
      const month = s.toLocaleDateString(undefined, { month: 'short' });
      return `${month} ${s.getDate()} – ${e.getDate()}, ${e.getFullYear()}`;
    }
    if (sameYear) {
      const sm = s.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
      const em = e.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
      return `${sm} – ${em}, ${e.getFullYear()}`;
    }
  }
  return [formatDate(start), formatDate(end)].filter(Boolean).join(' – ');
}

function CentreAvailabilityRow({ centre, expanded, onToggle }) {
  const location = getCentreLocation(centre);
  const title = getCentreTitle(centre);
  const capacity = getCentreCapacity(centre);

  // Build unique session columns from the union of all cohort sessions
  const { cohorts, groups, columnKeys } = useMemo(() => {
    const list =
      centre?.cohorts || centre?.programme_batches || centre?.batches || [];
    const orderedKeys = [];
    const seen = new Set();
    const groupMap = new Map();

    list.forEach((cohort) => {
      getCohortSessions(cohort).forEach((s) => {
        const label = getSessionLabel(s);
        const time = getSessionTimeLabel(s);
        const key = `${label}||${time}`;
        if (!seen.has(key)) {
          seen.add(key);
          orderedKeys.push({ key, label, time });
        }
        if (!groupMap.has(label)) groupMap.set(label, []);
        if (!groupMap.get(label).some((c) => c.key === key)) {
          groupMap.get(label).push({ key, time });
        }
      });
    });

    return {
      cohorts: list,
      columnKeys: orderedKeys,
      groups: Array.from(groupMap.entries()).map(([label, cols]) => ({ label, cols })),
    };
  }, [centre]);

  return (
    <div>
      <button
        onClick={onToggle}
        className="w-full text-left flex items-center gap-4 px-4 sm:px-5 py-4 hover:bg-gray-50/70 transition-colors"
      >
        <div className="min-w-0 flex-1">
          <h4 className="text-[15px] font-medium text-gray-900 leading-snug">
            {title}
          </h4>
          {location && (
            <p className="text-xs text-gray-500 mt-0.5 truncate">{location}</p>
          )}
        </div>

        <div className="hidden sm:flex items-center gap-5 text-xs text-gray-500 flex-shrink-0">
          <span>
            <span className="font-medium text-gray-900">{cohorts.length}</span>{' '}
            cohort{cohorts.length === 1 ? '' : 's'}
          </span>
          {capacity != null && (
            <span>
              cap.{' '}
              <span className="font-medium text-gray-900">{capacity}</span>
            </span>
          )}
        </div>

        <FiChevronDown
          className={`w-4 h-4 text-gray-400 flex-shrink-0 transition-transform ${
            expanded ? 'rotate-180' : ''
          }`}
        />
      </button>

      <AnimatePresence initial={false}>
        {expanded && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            transition={{ duration: 0.2, ease: 'easeOut' }}
            className="overflow-hidden"
          >
            <div className="px-4 sm:px-5 pb-5 pt-1">
              {cohorts.length === 0 ? (
                <p className="text-sm text-gray-400 py-3">
                  No cohorts scheduled yet.
                </p>
              ) : columnKeys.length > 0 ? (
                <ScheduleMatrix
                  cohorts={cohorts}
                  columnKeys={columnKeys}
                  groups={groups}
                />
              ) : (
                <CohortFallbackList cohorts={cohorts} />
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function ScheduleMatrix({ cohorts, columnKeys, groups }) {
  return (
    <div className="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
      <table className="w-full border-collapse text-sm">
        <thead>
          <tr>
            <th className="sticky left-0 bg-white z-10 text-left text-[11px] uppercase tracking-wider text-gray-400 font-medium pb-2 pr-4 align-bottom">
              Cohort
            </th>
            {groups.map((group, gi) => (
              <th
                key={group.label}
                colSpan={group.cols.length}
                className={`text-[11px] uppercase tracking-wider text-gray-400 font-medium pb-1 text-center ${
                  gi > 0 ? 'border-l border-gray-100' : ''
                }`}
              >
                {group.label}
              </th>
            ))}
          </tr>
          <tr className="border-b border-gray-100">
            <th className="sticky left-0 bg-white z-10"></th>
            {groups.map((group, gi) =>
              group.cols.map((col, ci) => (
                <th
                  key={col.key}
                  className={`text-[11px] font-medium text-gray-500 pb-2 px-2 text-center whitespace-nowrap ${
                    gi > 0 && ci === 0 ? 'border-l border-gray-100' : ''
                  }`}
                >
                  {col.time || '—'}
                </th>
              ))
            )}
          </tr>
        </thead>
        <tbody>
          {cohorts.map((cohort, i) => {
            const dates = formatDateRange(
              cohort?.start_date || cohort?.starts_at,
              cohort?.end_date || cohort?.ends_at
            );
            const sessionMap = new Map();
            getCohortSessions(cohort).forEach((s) => {
              const key = `${getSessionLabel(s)}||${getSessionTimeLabel(s)}`;
              sessionMap.set(key, s);
            });

            return (
              <tr
                key={cohort.id ?? i}
                className="border-b border-gray-50 last:border-b-0 hover:bg-gray-50/50 transition-colors"
              >
                <td className="sticky left-0 bg-white group-hover:bg-gray-50/50 z-10 py-3 pr-4 align-top whitespace-nowrap">
                  <div className="text-sm font-medium text-gray-900">
                    {getCohortLabel(cohort)}
                  </div>
                  {dates && (
                    <div className="text-xs text-gray-500 mt-0.5">{dates}</div>
                  )}
                </td>
                {groups.map((group, gi) =>
                  group.cols.map((col, ci) => {
                    const s = sessionMap.get(col.key);
                    const remaining = s ? getSessionRemaining(s) : null;
                    return (
                      <td
                        key={col.key}
                        className={`py-3 px-2 text-center align-middle ${
                          gi > 0 && ci === 0 ? 'border-l border-gray-100' : ''
                        }`}
                      >
                        <SeatCell remaining={remaining} present={!!s} />
                      </td>
                    );
                  })
                )}
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

function SeatCell({ remaining, present }) {
  if (!present) {
    return <span className="text-gray-300 text-sm">—</span>;
  }
  if (remaining == null) {
    return <span className="text-gray-500 text-sm">·</span>;
  }
  if (remaining === 0) {
    return (
      <span className="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-0.5 rounded text-[11px] font-medium text-gray-400 bg-gray-50">
        Full
      </span>
    );
  }
  const tone =
    remaining <= 2
      ? 'text-orange-700 bg-orange-50'
      : 'text-green-700 bg-green-50';
  return (
    <span
      className={`inline-flex items-center justify-center min-w-[2.5rem] px-2 py-0.5 rounded text-[12px] font-semibold tabular-nums ${tone}`}
    >
      {remaining}
    </span>
  );
}

function CohortFallbackList({ cohorts }) {
  return (
    <ul className="space-y-2 pt-1">
      {cohorts.map((cohort, i) => {
        const dates = formatDateRange(
          cohort?.start_date || cohort?.starts_at,
          cohort?.end_date || cohort?.ends_at
        );
        return (
          <li
            key={cohort.id ?? i}
            className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
          >
            <span className="text-sm font-medium text-gray-900">
              {getCohortLabel(cohort)}
            </span>
            {dates && <span className="text-xs text-gray-500">{dates}</span>}
          </li>
        );
      })}
    </ul>
  );
}
