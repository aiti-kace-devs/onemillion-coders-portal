"use client";

import { useState, useEffect } from "react";
import { useParams, useSearchParams } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import {
  FiMapPin,
  FiNavigation,
  FiExternalLink,
  FiArrowLeft,
  FiCheck,
  FiX,
  FiChevronLeft,
  FiChevronRight,
  FiMaximize2,
  FiPlay,
} from "react-icons/fi";
import {
  MdAccessible,
  MdRampRight,
  MdElevator,
  MdWc,
  MdHearing,
  MdVisibility,
  MdSchool,
} from "react-icons/md";
import { getCentreById } from "../../../services/pages";

export default function CenterDetailPage() {
  const params = useParams();
  const searchParams = useSearchParams();
  const centerId = Number(params.id);
  const regionName = searchParams.get("region") || "";
  const districtName = searchParams.get("district") || "";

  const [center, setCenter] = useState(null);
  const [loading, setLoading] = useState(true);
  const [imageErrors, setImageErrors] = useState({});
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [videoLightboxOpen, setVideoLightboxOpen] = useState(false);
  const [error, setError] = useState(null);

  const openVideoLightbox = () => {
    setVideoLightboxOpen(true);
    document.body.style.overflow = "hidden";
  };

  const closeVideoLightbox = () => {
    setVideoLightboxOpen(false);
    document.body.style.overflow = "unset";
  };

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const data = await getCentreById(centerId);
        if (data) {
          setCenter(data);
        } else {
          setError("Center not found.");
        }
      } catch (err) {
        console.error("Error fetching center:", err);
        setError("Failed to load center details.");
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [centerId]);

  const images = center
    ? [
        ...(Array.isArray(center.images) ? center.images : []),
        ...(center.image && !Array.isArray(center.images)
          ? [center.image]
          : []),
      ].filter((img) => img && img.trim() !== "" && !imageErrors[img])
    : [];

  const hasValidImage = images.length > 0;

  const openLightbox = (index) => {
    setActiveImageIndex(index);
    setLightboxOpen(true);
    document.body.style.overflow = "hidden";
  };

  const closeLightbox = () => {
    setLightboxOpen(false);
    document.body.style.overflow = "unset";
  };

  const nextImage = () => {
    setActiveImageIndex((prev) => (prev + 1) % images.length);
  };

  const prevImage = () => {
    setActiveImageIndex(
      (prev) => (prev - 1 + images.length) % images.length
    );
  };

  const accessibilityFeatures = center
    ? [
        {
          label: "Wheelchair accessible",
          value: center.wheelchair_accessible,
          icon: MdAccessible,
        },
        {
          label: "Access ramp",
          value: center.has_access_ramp,
          icon: MdRampRight,
        },
        {
          label: "Accessible toilet",
          value: center.has_accessible_toilet,
          icon: MdWc,
        },
        {
          label: "Elevator",
          value: center.has_elevator,
          icon: MdElevator,
        },
        {
          label: "Hearing impaired support",
          value: center.supports_hearing_impaired,
          icon: MdHearing,
        },
        {
          label: "Visually impaired support",
          value: center.supports_visually_impaired,
          icon: MdVisibility,
        },
        {
          label: "Staff trained for PWD",
          value: center.staff_trained_for_pwd,
          icon: MdSchool,
        },
      ]
    : [];

  const gpsInfo = center?.gps_location?.[0];
  const displayDistrict = gpsInfo?.District || districtName;
  const displayRegion = regionName;

  const directionsUrl = center
    ? gpsInfo?.Latitude && gpsInfo?.Longitude
      ? `https://www.google.com/maps/search/?api=1&query=${gpsInfo.Latitude},${gpsInfo.Longitude}`
      : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(center.title + ", " + displayDistrict + ", " + displayRegion + ", Ghana")}`
    : "#";

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="inline-flex items-center gap-3 px-5 py-3 rounded-full bg-white border border-gray-200 shadow-sm">
          <div className="w-5 h-5 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin" />
          <span className="text-sm text-gray-500 font-medium">
            Loading center details...
          </span>
        </div>
      </div>
    );
  }

  if (error || !center) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center gap-4">
        <p className="text-gray-500">{error || "Center not found."}</p>
        <Link
          href="/centers"
          className="text-yellow-600 font-medium hover:text-yellow-700 inline-flex items-center gap-2"
        >
          <FiArrowLeft className="w-4 h-4" />
          Back to Centers
        </Link>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero with Image Background */}
      <section className="relative h-[340px] sm:h-[420px] lg:h-[480px] overflow-hidden">
        {hasValidImage ? (
          <Image
            src={images[activeImageIndex] || images[0]}
            alt={center.title}
            fill
            className="object-cover"
            priority
            onError={() =>
              setImageErrors((prev) => ({
                ...prev,
                [images[activeImageIndex]]: true,
              }))
            }
          />
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900" />
        )}
        {/* Dark overlay gradient */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/20" />
        <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-yellow-400 to-green-500 z-10" />

        {/* Back button */}
        <div className="absolute top-4 left-4 sm:top-6 sm:left-6 z-10">
          <Link
            href="/centers"
            className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-black/30 backdrop-blur-md text-sm text-white hover:bg-black/50 transition-all border border-white/10"
          >
            <FiArrowLeft className="w-4 h-4" />
            Back to Centers
          </Link>
        </div>

        {/* Expand button */}
        {hasValidImage && (
          <button
            onClick={() => openLightbox(activeImageIndex)}
            className="absolute top-4 right-4 sm:top-6 sm:right-6 z-10 w-10 h-10 rounded-full bg-black/30 backdrop-blur-md flex items-center justify-center text-white hover:bg-black/50 transition-all border border-white/10"
          >
            <FiMaximize2 className="w-4 h-4" />
          </button>
        )}


        {/* Hero content */}
        <div className="absolute bottom-0 left-0 right-0 z-10">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
            >
              {center.is_pwd_friendly && (
                <div className="mb-3">
                  <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-500/20 backdrop-blur-sm text-purple-300 text-xs font-semibold border border-purple-500/20">
                    &#9855; PWD Friendly
                  </span>
                </div>
              )}

              <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-3 drop-shadow-lg">
                {center.title}
              </h1>

              {center.gps_address && (
                <div className="flex items-center gap-2 text-white/60">
                  <FiNavigation className="w-3.5 h-3.5" />
                  <span className="font-mono text-xs">
                    {center.gps_address}
                  </span>
                </div>
              )}
            </motion.div>
          </div>
        </div>

        {/* Image counter */}
        {images.length > 1 && (
          <div className="absolute bottom-8 right-4 sm:right-6 z-10 flex items-center gap-2">
            <button
              onClick={(e) => {
                e.stopPropagation();
                setActiveImageIndex(
                  (prev) => (prev - 1 + images.length) % images.length
                );
              }}
              className="w-8 h-8 rounded-full bg-black/30 backdrop-blur-md flex items-center justify-center text-white hover:bg-black/50 transition-all border border-white/10"
            >
              <FiChevronLeft className="w-4 h-4" />
            </button>
            <span className="text-white/90 text-xs font-medium bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10">
              {activeImageIndex + 1} / {images.length}
            </span>
            <button
              onClick={(e) => {
                e.stopPropagation();
                setActiveImageIndex(
                  (prev) => (prev + 1) % images.length
                );
              }}
              className="w-8 h-8 rounded-full bg-black/30 backdrop-blur-md flex items-center justify-center text-white hover:bg-black/50 transition-all border border-white/10"
            >
              <FiChevronRight className="w-4 h-4" />
            </button>
          </div>
        )}
      </section>

      {/* Thumbnail strip */}
      {(images.length > 1 || center.video) && (
        <div className="bg-white border-b border-gray-200">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div className="flex gap-2 overflow-x-auto scrollbar-hide">
              {center.video && (
                <button
                  onClick={openVideoLightbox}
                  className="relative flex-shrink-0 w-24 h-14 sm:w-28 sm:h-16 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-yellow-400 transition-all group"
                >
                  <div className="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black" />
                  <div className="absolute inset-0 flex items-center justify-center gap-1.5">
                    <span className="w-6 h-6 rounded-full bg-white/95 flex items-center justify-center group-hover:scale-110 transition-transform">
                      <FiPlay className="w-3 h-3 text-gray-900 ml-0.5" fill="currentColor" />
                    </span>
                    <span className="text-[10px] sm:text-xs font-semibold text-white uppercase tracking-wider">
                      Tour
                    </span>
                  </div>
                  <div className="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-red-500 via-yellow-400 to-green-500" />
                </button>
              )}
              {images.map((img, i) => (
                <button
                  key={i}
                  onClick={() => setActiveImageIndex(i)}
                  className={`relative flex-shrink-0 w-20 h-14 sm:w-24 sm:h-16 rounded-lg overflow-hidden border-2 transition-all ${
                    i === activeImageIndex
                      ? "border-yellow-400 ring-2 ring-yellow-100 shadow-md"
                      : "border-gray-200 hover:border-gray-300 opacity-70 hover:opacity-100"
                  }`}
                >
                  <Image
                    src={img}
                    alt={`${center.title} ${i + 1}`}
                    fill
                    className="object-cover"
                    onError={() =>
                      setImageErrors((prev) => ({
                        ...prev,
                        [img]: true,
                      }))
                    }
                  />
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Content */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main column */}
          <div className="lg:col-span-2 space-y-6">
            {/* Accessibility Features */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.1 }}
              className="bg-white rounded-2xl border border-gray-200 overflow-hidden"
            >
              <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 className="text-base font-bold text-gray-900">
                  Accessibility Features
                </h2>
              </div>
              <div className="p-6">
                {center.accessibility_rating && (
                  <div className="flex items-center gap-3 mb-5 pb-5 border-b border-gray-100">
                    <span className="text-sm text-gray-500">Rating</span>
                    <div className="flex items-center gap-1">
                      {Array.from({ length: 5 }).map((_, i) => (
                        <svg
                          key={i}
                          className={`w-5 h-5 ${
                            i < center.accessibility_rating
                              ? "text-yellow-400"
                              : "text-gray-200"
                          }`}
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                      ))}
                    </div>
                    <span className="text-sm font-semibold text-gray-700">
                      {center.accessibility_rating}/5
                    </span>
                  </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  {accessibilityFeatures.map((feature) => {
                    const Icon = feature.icon;
                    return (
                      <div
                        key={feature.label}
                        className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-colors ${
                          feature.value
                            ? "bg-green-50 border border-green-100"
                            : "bg-gray-50 border border-gray-100"
                        }`}
                      >
                        <div
                          className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${
                            feature.value
                              ? "bg-green-100"
                              : "bg-gray-100"
                          }`}
                        >
                          <Icon
                            className={`w-4.5 h-4.5 ${
                              feature.value
                                ? "text-green-600"
                                : "text-gray-400"
                            }`}
                          />
                        </div>
                        <div className="flex-1 min-w-0">
                          <span
                            className={`text-sm ${
                              feature.value
                                ? "text-green-700 font-medium"
                                : "text-gray-400"
                            }`}
                          >
                            {feature.label}
                          </span>
                        </div>
                        {feature.value ? (
                          <FiCheck className="w-4 h-4 text-green-500 flex-shrink-0" />
                        ) : (
                          <FiX className="w-4 h-4 text-gray-300 flex-shrink-0" />
                        )}
                      </div>
                    );
                  })}
                </div>

                {center.pwd_notes && (
                  <div className="mt-5 p-4 rounded-xl bg-purple-50 border border-purple-100">
                    <p className="text-sm text-purple-700 leading-relaxed">
                      <span className="font-semibold">Note:</span>{" "}
                      {center.pwd_notes}
                    </p>
                  </div>
                )}
              </div>
            </motion.div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6 lg:sticky lg:top-8 lg:self-start">
            {/* Get Directions card */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.15 }}
              className="bg-white rounded-2xl border border-gray-200 overflow-hidden"
            >
              <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 className="text-base font-bold text-gray-900">
                  Location
                </h3>
              </div>
              <div className="p-6">
                <div className="space-y-3 mb-5">
                  <div className="flex items-start gap-3">
                    <div className="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center flex-shrink-0">
                      <FiMapPin className="w-4 h-4 text-yellow-600" />
                    </div>
                    <div>
                      <p className="text-xs text-gray-400 font-medium uppercase tracking-wider mb-0.5">
                        District
                      </p>
                      <p className="text-sm text-gray-700 font-medium">
                        {displayDistrict}
                        {displayDistrict && displayRegion && ", "}
                        {displayRegion}
                      </p>
                    </div>
                  </div>
                  {center.gps_address && (
                    <div className="flex items-start gap-3">
                      <div className="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                        <FiNavigation className="w-4 h-4 text-gray-500" />
                      </div>
                      <div>
                        <p className="text-xs text-gray-400 font-medium uppercase tracking-wider mb-0.5">
                          GPS Address
                        </p>
                        <p className="text-sm text-gray-600 font-mono">
                          {center.gps_address}
                        </p>
                      </div>
                    </div>
                  )}
                </div>
                <a
                  href={directionsUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-r from-gray-900 to-gray-800 text-white text-sm font-semibold hover:from-gray-800 hover:to-gray-700 active:scale-[0.98] transition-all duration-200 shadow-sm"
                >
                  <FiNavigation className="w-4 h-4 text-yellow-400" />
                  Get Directions
                  <FiExternalLink className="w-3.5 h-3.5 text-gray-400" />
                </a>
              </div>
            </motion.div>

            {/* Quick Info card */}
            {/* <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.25 }}
              className="bg-white rounded-2xl border border-gray-200 overflow-hidden"
            >
              <div className="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 className="text-base font-bold text-gray-900">
                  Quick Info
                </h3>
              </div>
              <div className="p-6">
                <dl className="space-y-4">
                  <div className="flex items-center justify-between">
                    <dt className="text-sm text-gray-500">PWD Friendly</dt>
                    <dd className="text-sm font-medium text-gray-900">
                      {center.is_pwd_friendly ? (
                        <span className="text-green-600">Yes</span>
                      ) : (
                        "No"
                      )}
                    </dd>
                  </div>
                  {center.accessibility_rating && (
                    <>
                      <div className="h-px bg-gray-100" />
                      <div className="flex items-center justify-between">
                        <dt className="text-sm text-gray-500">
                          Accessibility
                        </dt>
                        <dd className="flex items-center gap-1">
                          {Array.from({ length: 5 }).map((_, i) => (
                            <svg
                              key={i}
                              className={`w-3.5 h-3.5 ${
                                i < center.accessibility_rating
                                  ? "text-yellow-400"
                                  : "text-gray-200"
                              }`}
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                          ))}
                        </dd>
                      </div>
                    </>
                  )}
                </dl>
              </div>
            </motion.div> */}
          </div>
        </div>
      </section>

      {/* Lightbox */}
      <AnimatePresence>
        {lightboxOpen && hasValidImage && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={closeLightbox}
          >
            <button
              onClick={closeLightbox}
              className="absolute top-4 right-4 md:top-6 md:right-6 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
            >
              <FiX className="w-5 h-5 md:w-6 md:h-6" />
            </button>

            {images.length > 1 && (
              <>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    prevImage();
                  }}
                  className="absolute left-4 md:left-6 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronLeft className="w-5 h-5 md:w-6 md:h-6" />
                </button>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    nextImage();
                  }}
                  className="absolute right-4 md:right-6 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronRight className="w-5 h-5 md:w-6 md:h-6" />
                </button>
              </>
            )}

            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              transition={{ duration: 0.3, ease: "easeOut" }}
              className="relative w-full h-full max-w-6xl max-h-[90vh]"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="relative w-full h-full rounded-lg overflow-hidden shadow-2xl">
                <Image
                  src={images[activeImageIndex]}
                  alt={center.title}
                  fill
                  className="object-contain"
                  sizes="(max-width: 768px) 100vw, (max-width: 1200px) 90vw, 1200px"
                />
              </div>
              {images.length > 1 && (
                <div className="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 backdrop-blur-sm px-3 py-1.5 rounded-full">
                  <span className="text-white text-sm font-medium">
                    {activeImageIndex + 1} / {images.length}
                  </span>
                </div>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Video Lightbox */}
      <AnimatePresence>
        {videoLightboxOpen && center.video && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={closeVideoLightbox}
          >
            <button
              onClick={closeVideoLightbox}
              className="absolute top-4 right-4 md:top-6 md:right-6 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
            >
              <FiX className="w-5 h-5 md:w-6 md:h-6" />
            </button>

            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              transition={{ duration: 0.3, ease: "easeOut" }}
              className="relative w-full max-w-6xl aspect-video"
              onClick={(e) => e.stopPropagation()}
            >
              <video
                src={center.video}
                controls
                autoPlay
                playsInline
                className="w-full h-full rounded-lg shadow-2xl bg-black"
              />
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
