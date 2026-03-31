"use client";

import { motion } from "framer-motion";
import {
  FiGlobe,
  FiZap,
  FiSearch,
} from "react-icons/fi";
import Button from "./Button";
import {
  GhanaGradientBar,
  GhanaGradientText,
} from "@/components/GhanaGradients";
import { useRouter } from "next/navigation";
import { useState, useEffect, useRef } from "react";
import { fetchBranchesSummary } from "@/services/api";

// Map SVG element IDs to display names
const REGION_MAP = {
  GHAA: "Greater Accra",
  GHAF: "Ahafo",
  GHAH: "Ashanti",
  GHBE: "Bono East",
  GHBO: "Bono",
  GHCP: "Central",
  GHEP: "Eastern",
  GHNE: "North East",
  GHNP: "Northern",
  GHOT: "Oti",
  GHSV: "Savannah",
  GHTV: "Volta",
  GHUE: "Upper East",
  GHUW: "Upper West",
  GHWN: "Western North",
  GHWP: "Western",
};


// Region center coordinates (% of viewBox) for tooltip positioning during auto-cycle
const REGION_CENTERS = {
  GHAA: { x: 62.73, y: 81.13 },
  GHAF: { x: 33.0, y: 61.83 },
  GHAH: { x: 43.26, y: 66.51 },
  GHBE: { x: 47.69, y: 51.46 },
  GHBO: { x: 31.68, y: 51.72 },
  GHCP: { x: 44.99, y: 83.43 },
  GHEP: { x: 56.61, y: 71.77 },
  GHNE: { x: 54.89, y: 16.22 },
  GHNP: { x: 59.5, y: 24.87 },
  GHOT: { x: 68.2, y: 50.74 },
  GHSV: { x: 38.69, y: 32.02 },
  GHTV: { x: 71.31, y: 73.38 },
  GHUE: { x: 50.15, y: 10.31 },
  GHUW: { x: 33.68, y: 15.43 },
  GHWN: { x: 25.3, y: 74.63 },
  GHWP: { x: 34.18, y: 87.01 },
};

// Matte fill colors for each region
const REGION_COLORS = {
  GHAA: "#2D6A4F",
  GHAF: "#7CB68E",
  GHAH: "#40916C",
  GHBE: "#95D5B2",
  GHBO: "#2D6A4F",
  GHCP: "#74C69D",
  GHEP: "#1B4332",
  GHNE: "#52B788",
  GHNP: "#2D6A4F",
  GHOT: "#8FC9A3",
  GHSV: "#40916C",
  GHTV: "#368B5E",
  GHUE: "#74C69D",
  GHUW: "#1B4332",
  GHWN: "#95D5B2",
  GHWP: "#52B788",
};

// Region IDs ordered top to bottom by geographic position
const REGION_ORDER = [
  "GHUE", "GHUW", "GHNE", "GHNP", "GHSV", "GHOT",
  "GHBE", "GHBO", "GHAF", "GHAH", "GHEP", "GHTV",
  "GHWN", "GHAA", "GHCP", "GHWP",
];

const TechGhanaSection = () => {
  const router = useRouter();
  const [hoveredRegion, setHoveredRegion] = useState(null);
  const [branchesData, setBranchesData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [svgContent, setSvgContent] = useState("");
  const mapRef = useRef(null);
  const tooltipRef = useRef(null);
  const userInteracting = useRef(false);
  const autoIndexRef = useRef(0);
  const autoRegionRef = useRef(null);
  const [mapInView, setMapInView] = useState(true);

  // Compute tooltip position as percentages relative to the map container
  const getTooltipPosition = (regionId) => {
    const center = REGION_CENTERS[regionId];
    if (!center) return { left: "0%", top: "0%" };
    return { left: `${center.x}%`, top: `${center.y}%` };
  };

  // Fetch and process SVG map
  useEffect(() => {
    fetch("/images/gh.svg")
      .then((res) => res.text())
      .then((raw) => {
        let processed = raw
          // Remove XML declaration
          .replace(/<\?xml[^?]*\?>/g, "")
          // Remove comments
          .replace(/<!--[\s\S]*?-->/g, "")
          // Fix viewbox -> viewBox (case-sensitive SVG attribute)
          .replace(/viewbox=/gi, "viewBox=")
          // Remove fixed width/height so CSS can control sizing
          .replace(/\s+width="1000"/, "")
          .replace(/\s+height="1000"/, "")
          .replace(/\s+height="1000"/, "");

        // Build per-region color rules + base styles
        const regionColorRules = Object.entries(REGION_COLORS)
          .map(([id, color]) => `#features path#${id} { fill: ${color}; }`)
          .join("\n          ");

        const styleTag = `<style>
          #features path {
            transition: fill 0.6s ease-in-out, filter 0.6s ease-in-out;
            cursor: pointer;
          }
          ${regionColorRules}
          #features path:hover {
            fill: #d4a017 !important;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
          }
        </style>`;
        // Insert style tag right after the opening <svg ...> tag
        processed = processed.replace(/(xmlns="[^"]*">)/, "$1\n" + styleTag);

        setSvgContent(processed);
      })
      .catch(console.error);
  }, []);

  // Fetch branches data
  useEffect(() => {
    const loadBranchesData = async () => {
      try {
        setLoading(true);
        const response = await fetchBranchesSummary();
        if (response?.success && response?.data) {
setBranchesData(response.data);
        }
      } catch (error) {
        console.error("Failed to load branches data:", error);
      } finally {
        setLoading(false);
      }
    };
    loadBranchesData();
  }, []);

  // Hide circles/labels once SVG is loaded
  useEffect(() => {
    if (!mapRef.current || !svgContent) return;
    const svg = mapRef.current.querySelector("svg");
    if (!svg) return;

    const circlesGroup = svg.querySelector("#circles");
    if (circlesGroup) circlesGroup.style.display = "none";
    const labelsGroup = svg.querySelector("#labels");
    if (labelsGroup) labelsGroup.style.display = "none";
  }, [svgContent]);

  // Use event delegation on container for reliable hover tracking
  useEffect(() => {
    const container = mapRef.current;
    if (!container) return;

    const handleMouseOver = (e) => {
      const path = e.target.closest("#features path");
      if (path && path.id) {
        userInteracting.current = true;
        setHoveredRegion(path.id);
      }
    };

    const handleClick = (e) => {
      const path = e.target.closest("#features path");
      if (path && path.id && REGION_MAP[path.id]) {
        router.push(`/centers?region=${encodeURIComponent(REGION_MAP[path.id])}`);
      }
    };

    const handleMouseLeave = () => {
      userInteracting.current = false;
      setHoveredRegion(null);
    };

    container.addEventListener("mouseover", handleMouseOver);
    container.addEventListener("mouseleave", handleMouseLeave);
    container.addEventListener("click", handleClick);

    return () => {
      container.removeEventListener("mouseover", handleMouseOver);
      container.removeEventListener("mouseleave", handleMouseLeave);
      container.removeEventListener("click", handleClick);
    };
  }, [svgContent, router]);

  // Track if map is in viewport
  useEffect(() => {
    if (!mapRef.current) return;
    const observer = new IntersectionObserver(
      ([entry]) => setMapInView(entry.isIntersecting),
      { threshold: 0.1 }
    );
    observer.observe(mapRef.current);
    return () => observer.disconnect();
  }, [svgContent]);

  // Auto-cycle through regions top to bottom
  useEffect(() => {
    if (!mapRef.current || !svgContent) return;

    const interval = setInterval(() => {
      if (userInteracting.current || !mapInView) return;

      const regionId = REGION_ORDER[autoIndexRef.current];
      autoRegionRef.current = regionId;

      setHoveredRegion(regionId);
      autoIndexRef.current = (autoIndexRef.current + 1) % REGION_ORDER.length;
    }, 2000);

    return () => clearInterval(interval);
  }, [svgContent, mapInView]);

  // Highlight hovered region by setting inline styles directly on the SVG path
  useEffect(() => {
    if (!mapRef.current) return;
    const svg = mapRef.current.querySelector("svg");
    if (!svg) return;

    // Clear previous highlight
    const allPaths = svg.querySelectorAll("#features path");
    allPaths.forEach((p) => {
      p.style.fill = "";
      p.style.filter = "";
    });

    // Apply highlight to current region
    if (hoveredRegion) {
      const path = svg.querySelector(`#features path#${hoveredRegion}`);
      if (path) {
        path.style.fill = "#d4a017";
        path.style.filter = "drop-shadow(0 4px 6px rgba(0,0,0,0.3))";
      }
    }
  }, [hoveredRegion]);

  // Find branch data for a hovered region
  // Normalizes names to handle variations like "Greater Accra" vs "Greater Accra Region",
  // "North East" vs "Northern East", etc.
  const getBranchData = (regionId) => {
    const regionName = REGION_MAP[regionId];
    if (!regionName) return null;

    const normalize = (str) =>
      str
        .toLowerCase()
        .replace(/\s*region\s*/g, "")
        .replace(/northern\s+east/, "north east")
        .trim();

    const normalizedRegion = normalize(regionName);

    const match = branchesData.find((b) => {
      return normalize(b.branch_title) === normalizedRegion;
    });

    return match;
  };

  const hoveredBranchData = hoveredRegion
    ? getBranchData(hoveredRegion)
    : null;

  return (
    <section className="relative section-spacing bg-white overflow-visible py-20">
      {/* Clean geometric background */}
      <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100"></div>

      {/* Subtle Ghana flag accent line */}
      <GhanaGradientBar height="3px" position="top" />

      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Two-Column Layout: Text + Map */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.8, delay: 0.2 }}
          className="relative mb-20"
        >
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            {/* Left Column - Text Content */}
            <div className="space-y-6">
              <h3 className="heading-lg text-gray-900 content-spacing">
                Global Quality,
                <GhanaGradientText className="block">
                  Ghanaian Accessibility.
                </GhanaGradientText>
              </h3>
              <p className="text-lead text-gray-600">
                Our partnership with leading organizations allows us to offer
                eligible candidates sponsored access to world-class programme
                training, our vibrant community, and state-of-the-art in-person
                infrastructure, delivering an unparalleled learning experience
                for thousands of learners across Ghana.
              </p>
              <div className="pt-4">
                <p className="text-sm text-gray-500">
                  Hover over any region on the map to explore training
                  programs and available courses
                </p>
              </div>
            </div>

            {/* Right Column - Interactive SVG Map */}
            <div className="relative">
              <div className="relative h-96 lg:h-[500px]">
                {/* Loading Overlay */}
                {loading && (
                  <div className="absolute inset-0 bg-white/50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="flex items-center space-x-3 bg-white rounded-lg px-4 py-3 shadow-lg">
                      <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-red-600"></div>
                      <span className="text-sm text-gray-700 font-medium">
                        Loading branch data...
                      </span>
                    </div>
                  </div>
                )}

                {/* Region highlight applied via useEffect on the SVG path directly */}

                {/* SVG Map */}
                <div
                  ref={mapRef}
                  className="absolute inset-0 [&_svg]:w-full [&_svg]:h-full"
                  dangerouslySetInnerHTML={{ __html: svgContent }}
                />

                {/* Region Tooltip - always rendered, visibility toggled */}
                <div
                  ref={tooltipRef}
                  className={`absolute z-[1001] pointer-events-none transition-all duration-500 ease-in-out ${
                    hoveredRegion && mapInView ? "opacity-100 scale-100" : "opacity-0 scale-95"
                  }`}
                  style={{
                    ...(hoveredRegion ? getTooltipPosition(hoveredRegion) : {}),
                    transform: "translate(-50%, -110%)",
                  }}
                >
                    <div className="bg-white rounded-xl px-3 py-2.5 shadow-2xl border border-gray-100 w-52">
                      <h4 className="font-semibold text-gray-900 text-sm mb-2">
                        {REGION_MAP[hoveredRegion]} Region
                      </h4>

                      {hoveredBranchData ? (
                        <>
                          {/* Stats row */}
                          <div className="mb-2">
                            <div className="bg-gray-50 rounded-md px-2 py-1.5 text-center">
                              <span className="text-sm font-bold text-gray-900">
                                {hoveredBranchData.total_centres}
                              </span>
                              <span className="text-[11px] text-gray-500 ml-1">
                                center{hoveredBranchData.total_centres !== 1 ? "s" : ""}
                              </span>
                            </div>
                          </div>

                          {/* Course tags */}
                          {hoveredBranchData.courses?.length > 0 && (
                            <div className="grid grid-cols-2 gap-1">
                              {hoveredBranchData.courses.slice(0, 4).map((course, i) => (
                                <span
                                  key={i}
                                  className="text-[11px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-medium text-center truncate"
                                >
                                  {course.title}
                                </span>
                              ))}
                              {hoveredBranchData.courses.length > 4 && (
                                <span className="text-[11px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-center">
                                  +{hoveredBranchData.courses.length - 4} more
                                </span>
                              )}
                            </div>
                          )}
                        </>
                      ) : (
                        <p className="text-xs text-gray-400">No active programs</p>
                      )}
                    </div>
                </div>
              </div>
            </div>
          </div>
        </motion.div>

        {/* Call to Action */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.6 }}
          className="text-center"
        >
          <div className="bg-gradient-to-r from-red-600 to-green-600 rounded-3xl p-8 text-white">
            <div className="flex items-center justify-center mb-4">
              <FiZap className="w-8 h-8 text-yellow-300 mr-3" />
              <h3 className="text-2xl font-bold text-white">
                Ready to Join Ghana&apos;s Tech Revolution?
              </h3>
            </div>
            <p className="text-white/90 mb-6 max-w-2xl mx-auto">
              Be part of the movement that&apos;s positioning Ghana as a leading
              tech hub in Africa. Start your journey with world-class training
              and certification programs.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button
                onClick={() => router.push("/programmes")}
                variant="primary"
                icon={FiGlobe}
                className="!bg-white !text-gray-900 hover:!bg-gray-100 !border-white"
              >
                Explore Programs
              </Button>
              <Button
                onClick={() => router.push("/course-match")}
                variant="outline"
                icon={FiSearch}
                className="!bg-transparent !border-white !text-white hover:!bg-white hover:!text-gray-900"
              >
                Course Match
              </Button>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Bottom Ghana Flag Border */}
      <GhanaGradientBar height="3px" position="bottom" />
    </section>
  );
};

export default TechGhanaSection;
