"use client";

import { motion } from "framer-motion";
import {
  FiGlobe,
  FiUsers,
  FiMapPin,
  FiZap,
  FiSearch,
  FiBook,
} from "react-icons/fi";
import Button from "./Button";
import Image from "next/image";
import { GhanaGradientBar, GhanaGradientText } from "@/components/GhanaGradients";
import { useRouter } from "next/navigation";
import { useState, useEffect } from "react";
import { fetchBranchesSummary } from "@/services/api";

const TechGhanaSection = () => {
  const router = useRouter();
  const [activeTooltip, setActiveTooltip] = useState(null);
  const [branchesData, setBranchesData] = useState([]);
  const [loading, setLoading] = useState(true);

  // Region position mapping for regions with active programs
  const regionPositions = {
    "Greater Accra Region": "top-[83%] left-[63%]", // Southeast coastal area
    "Ashanti Region": "top-[70%] left-[42%]", // Central-south area where Kumasi is located
    "Bono Region": "top-[47%] left-[50%]", // West-central area
    "Upper East Region": "top-[12%] left-[60%]", // Northeast area
  };

  // Fetch branches data on component mount
  useEffect(() => {
    const loadBranchesData = async () => {
      try {
        setLoading(true);
        const response = await fetchBranchesSummary();
        if (response?.success && response?.data) {
          setBranchesData(response.data);
        }
      } catch (error) {
        console.error('Failed to load branches data:', error);
        // Component will fall back to default data
      } finally {
        setLoading(false);
      }
    };

    loadBranchesData();
  }, []);

  // Create regions array from API data only
  const regions = branchesData
    .filter(branch => regionPositions[branch.branch_title]) // Only include regions we have positions for
    .map(apiData => {
      const courseTitles = apiData.courses?.map(course => course.title).join(', ') || 'Various Programs';
      return {
        name: apiData.branch_title,
        position: regionPositions[apiData.branch_title],
        coders: `${apiData.total_trained_coders.toLocaleString()}+`,
        programs: courseTitles,
        centers: `${apiData.total_centres} Training Center${apiData.total_centres !== 1 ? 's' : ''}`,
        hasApiData: true,
        courses: apiData.courses || []
      };
    });

  return (
    <section className="relative section-spacing bg-white overflow-visible py-20">
      {/* Clean geometric background */}
      <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100"></div>

      {/* Subtle Ghana flag accent line */}
      <GhanaGradientBar height="3px" position="top" />

      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        {/* <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-center mb-16"
        >
          <div className="flex items-center justify-center mb-4">
            <div className="flex items-center space-x-2">
              <FiMapPin className="w-6 h-6 text-red-600" />
              <span className="text-red-600 font-semibold text-sm uppercase tracking-wide">
                One Million Coders Ghana
              </span>
            </div>
          </div>
          <h2 className="text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
            Empowering Ghana&apos;s
            <span className="block text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-yellow-500 to-green-600">
              Digital Transformation
            </span>
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
            Building world-class tech talent across Ghana&apos;s regions with
            comprehensive training programs and industry-recognized
            certifications.
          </p>
        </motion.div> */}

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
                <div className="flex items-center space-x-2">
                  <div className="w-2 h-2 bg-red-600 rounded-full animate-pulse"></div>
                  <p className="text-sm text-gray-500">
                    Click on any active region to explore training programs, enrollment data, and available courses
                  </p>
                </div>
              </div>
            </div>

            {/* Right Column - Ghana Map */}
            <div className="relative">
              <div className="relative h-96 lg:h-[500px] overflow-visible">
                {/* Map Background */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <Image
                    src="/images/ghana/map-pattern.png"
                    alt="Ghana Map"
                    fill
                    className="object-contain opacity-60"
                  />
                </div>

                {/* Loading Overlay */}
                {loading && (
                  <div className="absolute inset-0 bg-white/50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div className="flex items-center space-x-3 bg-white rounded-lg px-4 py-3 shadow-lg">
                      <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-red-600"></div>
                      <span className="text-sm text-gray-700 font-medium">Loading branch data...</span>
                    </div>
                  </div>
                )}

                {/* Regional Markers */}
                {regions.length === 0 && !loading && (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="bg-white rounded-lg px-6 py-4 shadow-lg border border-gray-200">
                      <p className="text-gray-600 text-center">
                        No active training regions available at the moment
                      </p>
                    </div>
                  </div>
                )}
                {regions.map((region, index) => (
                  <motion.div
                    key={region.name}
                    initial={{ opacity: 0, scale: 0 }}
                    whileInView={{ opacity: 1, scale: 1 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.5, delay: index * 0.1 }}
                    className={`absolute ${region.position} transform -translate-x-1/2 -translate-y-1/2`}
                    style={{ zIndex: activeTooltip === index ? 1000 : 10 }}
                  >
                    <div
                      className="group relative cursor-pointer"
                      onClick={() =>
                        setActiveTooltip(activeTooltip === index ? null : index)
                      }
                      onMouseEnter={() => setActiveTooltip(index)}
                      onMouseLeave={() => setActiveTooltip(null)}
                    >
                      {/* Clean Simple Marker */}
                      <div className="w-4 h-4 bg-red-600 rounded-full border border-white hover:scale-125 transition-transform duration-200 shadow-sm"></div>
                      <div className="absolute w-6 h-6 bg-red-600/20 rounded-full -top-1 -left-1 animate-pulse"></div>

                      {/* Enhanced Tooltip with smart positioning */}
                      <div
                        className={`absolute ${
                          // Upper regions (top of map) show tooltips below, lower regions show tooltips above
                          region.position.includes('top-[12%]') || region.position.includes('top-[47%]')
                            ? 'top-8' // Upper regions: tooltip below marker
                            : 'bottom-8' // Lower regions: tooltip above marker  
                        } left-1/2 transform -translate-x-1/2 transition-all duration-300 z-[1001] ${
                          activeTooltip === index
                            ? "opacity-100 pointer-events-auto"
                            : "opacity-0 pointer-events-none"
                        }`}
                      >
                        <div className="bg-white rounded-lg p-4 shadow-2xl border border-gray-200 min-w-56 max-w-72 relative">
                          <div className="flex items-center justify-between mb-3">
                            <h4 className="font-semibold text-gray-900 text-base">
                              {region.name}
                            </h4>
                          </div>
                          <div className="space-y-2">
                            {/* <p className="text-sm text-gray-700 flex items-center">
                              <FiUsers className="w-4 h-4 mr-2 text-blue-600" />
                              <span className="font-medium">
                                {region.coders}
                              </span>{" "}
                              coders trained
                            </p> */}
                            <p className="text-sm text-gray-700 flex items-center">
                              <FiMapPin className="w-4 h-4 mr-2 text-green-600" />
                              {region.centers}
                            </p>
                            <p className="text-sm text-gray-700 flex items-center">
                              <FiBook className="w-4 h-4 mr-2 text-orange-600" />
                              {region.courses.length} Active Course{region.courses.length !== 1 ? 's' : ''}
                            </p>
                            <div className="pt-2 border-t border-gray-200 mt-3">
                              <p className="text-sm text-gray-800 font-medium">
                                Programs:
                              </p>
                              <p className="text-sm text-gray-600 mt-1 leading-relaxed">
                                {region.programs.length > 50 
                                  ? region.programs.substring(0, 50) + '...'
                                  : region.programs}
                              </p>
                            </div>
                          </div>
                        </div>
                        {/* Tooltip arrow with correct direction */}
                        <div className={`absolute left-1/2 transform -translate-x-1/2 ${
                          region.position.includes('top-[12%]') || region.position.includes('top-[47%]')
                            ? 'bottom-full -mb-1' // Upper regions: arrow points up (tooltip below marker)
                            : 'top-full -mt-1' // Lower regions: arrow points down (tooltip above marker)
                        }`}>
                          <div className={`w-3 h-3 bg-white rotate-45 border-gray-200 ${
                            region.position.includes('top-[12%]') || region.position.includes('top-[47%]')
                              ? 'border-l border-t' // Upper regions: arrow pointing up
                              : 'border-r border-b' // Lower regions: arrow pointing down
                          }`}></div>
                        </div>
                      </div>
                    </div>
                  </motion.div>
                ))}
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
