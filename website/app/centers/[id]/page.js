"use client";

import { useState, useEffect } from "react";
import { useParams, useSearchParams } from "next/navigation";
import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import {
  FiMapPin,
  FiNavigation,
  FiExternalLink,
  FiArrowLeft,
  FiCheck,
  FiX,
} from "react-icons/fi";
import {
  getCentresByDistrict,
  getCentreProgrammes,
} from "../../../services/pages";

export default function CenterDetailPage() {
  const params = useParams();
  const searchParams = useSearchParams();
  const centerId = Number(params.id);
  const districtId = searchParams.get("district_id");
  const regionName = searchParams.get("region") || "";
  const districtName = searchParams.get("district") || "";

  const [center, setCenter] = useState(null);
  const [programmes, setProgrammes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [imageError, setImageError] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);

        // Fetch center data
        if (districtId) {
          const data = await getCentresByDistrict(districtId);
          const centres = data?.centres || [];
          const found = centres.find((c) => c.id === centerId);
          if (found) setCenter(found);
          else setError("Center not found.");
        } else {
          setError("Missing district information.");
        }

        // Fetch programmes for this center
        try {
          const progData = await getCentreProgrammes(centerId);
          setProgrammes(progData?.programmes || progData || []);
        } catch {
          // Programmes may not be available
        }
      } catch (err) {
        console.error("Error fetching center:", err);
        setError("Failed to load center details.");
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [centerId, districtId]);

  const hasValidImage =
    center?.image && center.image.trim() !== "" && !imageError;

  const accessibilityFeatures = center
    ? [
        {
          label: "Wheelchair accessible",
          value: center.wheelchair_accessible,
        },
        { label: "Access ramp", value: center.has_access_ramp },
        { label: "Accessible toilet", value: center.has_accessible_toilet },
        { label: "Elevator", value: center.has_elevator },
        {
          label: "Hearing impaired support",
          value: center.supports_hearing_impaired,
        },
        {
          label: "Visually impaired support",
          value: center.supports_visually_impaired,
        },
        {
          label: "Staff trained for PWD",
          value: center.staff_trained_for_pwd,
        },
      ]
    : [];

  const directionsUrl = center
    ? center.gps_address
      ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(center.gps_address)}`
      : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(center.title + ", " + districtName + ", " + regionName + ", Ghana")}`
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

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
          {/* Back link */}
          <Link
            href="/centers"
            className="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors mb-6"
          >
            <FiArrowLeft className="w-4 h-4" />
            Back to Centers
          </Link>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
              {center.title}
            </h1>
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex items-center gap-2 text-gray-400">
                <FiMapPin className="w-4 h-4 text-yellow-400" />
                <span>
                  {districtName}
                  {districtName && regionName && ", "}
                  {regionName}
                </span>
              </div>
              {center.gps_address && (
                <div className="flex items-center gap-2 text-gray-500">
                  <FiNavigation className="w-3.5 h-3.5" />
                  <span className="font-mono text-sm">
                    {center.gps_address}
                  </span>
                </div>
              )}
              {center.is_pwd_friendly && (
                <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-semibold">
                  &#9855; PWD Friendly
                </span>
              )}
            </div>
          </motion.div>
        </div>
      </section>

      {/* Content */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main column */}
          <div className="lg:col-span-2 space-y-8">
            {/* Center image */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
              className="relative rounded-2xl overflow-hidden bg-gray-100 aspect-video"
            >
              {hasValidImage ? (
                <Image
                  src={center.image}
                  alt={center.title}
                  fill
                  className="object-cover"
                  onError={() => setImageError(true)}
                />
              ) : (
                <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                  <Image
                    src="/images/one-million-coders-logo.png"
                    alt="One Million Coders"
                    width={200}
                    height={65}
                    className="opacity-15"
                  />
                </div>
              )}
            </motion.div>

            {/* Accessibility Details */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.1 }}
              className="bg-white rounded-2xl border border-gray-200 p-6"
            >
              <h2 className="text-lg font-bold text-gray-900 mb-4">
                Accessibility Features
              </h2>
              {center.accessibility_rating && (
                <div className="flex items-center gap-3 mb-5 pb-5 border-b border-gray-100">
                  <span className="text-sm text-gray-500">
                    Accessibility Rating
                  </span>
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
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {accessibilityFeatures.map((feature) => (
                  <div
                    key={feature.label}
                    className={`flex items-center gap-3 px-4 py-3 rounded-xl border ${
                      feature.value
                        ? "bg-green-50 border-green-200"
                        : "bg-gray-50 border-gray-100"
                    }`}
                  >
                    {feature.value ? (
                      <FiCheck className="w-4 h-4 text-green-500 flex-shrink-0" />
                    ) : (
                      <FiX className="w-4 h-4 text-gray-300 flex-shrink-0" />
                    )}
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
                ))}
              </div>

              {center.pwd_notes && (
                <div className="mt-5 p-4 rounded-xl bg-purple-50 border border-purple-100">
                  <p className="text-sm text-purple-700 leading-relaxed">
                    <span className="font-semibold">Note:</span>{" "}
                    {center.pwd_notes}
                  </p>
                </div>
              )}
            </motion.div>

            {/* Programmes */}
            {Array.isArray(programmes) && programmes.length > 0 && (
              <motion.div
                initial={{ opacity: 0, y: 16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.4, delay: 0.2 }}
                className="bg-white rounded-2xl border border-gray-200 p-6"
              >
                <h2 className="text-lg font-bold text-gray-900 mb-4">
                  Available Programmes
                </h2>
                <div className="space-y-3">
                  {programmes.map((prog) => (
                    <Link
                      key={prog.id}
                      href={`/programmes/${prog.id}`}
                      className="flex items-center justify-between p-4 rounded-xl border border-gray-200 hover:border-yellow-300 hover:shadow-sm transition-all group"
                    >
                      <div>
                        <h3 className="text-sm font-semibold text-gray-900 group-hover:text-yellow-700 transition-colors">
                          {prog.title}
                        </h3>
                        {prog.duration && (
                          <p className="text-xs text-gray-400 mt-0.5">
                            {prog.duration}
                          </p>
                        )}
                      </div>
                      <FiArrowLeft className="w-4 h-4 text-gray-300 rotate-180 group-hover:text-yellow-600 group-hover:translate-x-0.5 transition-all" />
                    </Link>
                  ))}
                </div>
              </motion.div>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Get Directions card */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.15 }}
              className="bg-white rounded-2xl border border-gray-200 p-6"
            >
              <h3 className="text-sm font-bold text-gray-900 mb-4">
                Location
              </h3>
              <div className="space-y-3 mb-5">
                <div className="flex items-start gap-3">
                  <FiMapPin className="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="text-sm text-gray-700 font-medium">
                      {districtName}
                      {districtName && regionName && ", "}
                      {regionName}
                    </p>
                  </div>
                </div>
                {center.gps_address && (
                  <div className="flex items-start gap-3">
                    <FiNavigation className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" />
                    <p className="text-sm text-gray-500 font-mono">
                      {center.gps_address}
                    </p>
                  </div>
                )}
              </div>
              <a
                href={directionsUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 active:scale-[0.98] transition-all duration-200"
              >
                <FiNavigation className="w-4 h-4 text-yellow-400" />
                Get Directions
                <FiExternalLink className="w-3.5 h-3.5 text-gray-400" />
              </a>
            </motion.div>

            {/* Quick Info card */}
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.25 }}
              className="bg-white rounded-2xl border border-gray-200 p-6"
            >
              <h3 className="text-sm font-bold text-gray-900 mb-4">
                Quick Info
              </h3>
              <dl className="space-y-3">
                <div className="flex justify-between">
                  <dt className="text-sm text-gray-500">Status</dt>
                  <dd>
                    <span
                      className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                        center.status
                          ? "bg-green-50 text-green-600"
                          : "bg-red-50 text-red-600"
                      }`}
                    >
                      {center.status ? "Active" : "Inactive"}
                    </span>
                  </dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-sm text-gray-500">PWD Friendly</dt>
                  <dd className="text-sm font-medium text-gray-900">
                    {center.is_pwd_friendly ? "Yes" : "No"}
                  </dd>
                </div>
                {center.accessibility_rating && (
                  <div className="flex justify-between">
                    <dt className="text-sm text-gray-500">
                      Accessibility Rating
                    </dt>
                    <dd className="text-sm font-medium text-gray-900">
                      {center.accessibility_rating}/5
                    </dd>
                  </div>
                )}
                {Array.isArray(programmes) && programmes.length > 0 && (
                  <div className="flex justify-between">
                    <dt className="text-sm text-gray-500">Programmes</dt>
                    <dd className="text-sm font-medium text-gray-900">
                      {programmes.length}
                    </dd>
                  </div>
                )}
              </dl>
            </motion.div>
          </div>
        </div>
      </section>
    </div>
  );
}
