"use client";

import { motion } from "framer-motion";
import {
  FiGlobe,
  FiTrendingUp,
  FiUsers,
  FiMapPin,
  FiStar,
  FiZap,
  FiSearch,
} from "react-icons/fi";
import Button from "./Button";
import Image from "next/image";
import { GhanaGradientBar, GhanaGradientText } from "@/components/GhanaGradients";
import { useRouter } from "next/navigation";
import { useState } from "react";

const TechGhanaSection = () => {
  const router = useRouter();
  const [activeTooltip, setActiveTooltip] = useState(null);
  const regions = [
    {
      name: "Greater Accra",
      coders: "45,000+",
      programs: "Cybersecurity, Data Protection, AI Training",
      centers: "12 Training Centers",
      position: "top-[83%] left-[63%]", // Southeast coastal area where Accra is located
    },
    {
      name: "Ashanti Region",
      coders: "32,500+",
      programs: "Web Development, Mobile Apps, BPO Training",
      centers: "8 Training Centers",
      position: "top-[70%] left-[42%]", // Central-south area where Kumasi is located
    },
    {
      name: "Bono Region",
      coders: "18,200+",
      programs: "Systems Admin, Data Analytics, Cybersecurity",
      centers: "5 Training Centers",
      position: "top-[47%] left-[50%]", // West-central area, former Brong-Ahafo region
    },
    {
      name: "Upper East Region",
      coders: "15,800+",
      programs: "Digital Literacy, Web Programming, Data Protection",
      centers: "4 Training Centers",
      position: "top-[12%] left-[60%]", // Northeast area near Burkina Faso border
    },
  ];

  return (
    <section className="relative section-spacing bg-white overflow-hidden">
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
                <p className="text-sm text-gray-500 mb-4">
                  Click on regions to explore training programs and impact:
                </p>
                <div className="flex flex-wrap gap-2">
                  {regions.map((region, index) => (
                    <div
                      key={region.name}
                      className="flex items-center space-x-2 bg-gray-100 rounded-full px-3 py-1"
                    >
                      <div className="w-2 h-2 bg-red-600 rounded-full"></div>
                      <span className="text-sm text-gray-700">
                        {region.name}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Right Column - Ghana Map */}
            <div className="relative">
              <div className="relative h-96 lg:h-[500px]">
                {/* Map Background */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <Image
                    src="/images/ghana/map-pattern.png"
                    alt="Ghana Map"
                    fill
                    className="object-contain opacity-60"
                  />
                </div>

                {/* Regional Markers */}
                {regions.map((region, index) => (
                  <motion.div
                    key={region.name}
                    initial={{ opacity: 0, scale: 0 }}
                    whileInView={{ opacity: 1, scale: 1 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.5, delay: index * 0.1 }}
                    className={`absolute ${region.position} transform -translate-x-1/2 -translate-y-1/2`}
                    style={{ zIndex: activeTooltip === index ? 50 : 10 }}
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

                      {/* Simple Tooltip */}
                      <div
                        className={`absolute bottom-8 left-1/2 transform -translate-x-1/2 transition-all duration-300 ${
                          activeTooltip === index
                            ? "opacity-100 pointer-events-auto"
                            : "opacity-0 pointer-events-none"
                        }`}
                      >
                        <div className="bg-white rounded-lg p-3 shadow-xl border border-gray-200 min-w-56 max-w-64">
                          <h4 className="font-semibold text-gray-900 text-sm mb-2">
                            {region.name}
                          </h4>
                          <div className="space-y-1">
                            <p className="text-xs text-gray-600 flex items-center">
                              <FiUsers className="w-3 h-3 mr-2 text-blue-600" />
                              <span className="font-medium">
                                {region.coders}
                              </span>{" "}
                              coders trained
                            </p>
                            <p className="text-xs text-gray-600 flex items-center">
                              <FiMapPin className="w-3 h-3 mr-2 text-green-600" />
                              {region.centers}
                            </p>
                            <div className="pt-1 border-t border-gray-100 mt-2">
                              <p className="text-xs text-gray-700">
                                {region.programs}
                              </p>
                            </div>
                          </div>
                        </div>
                        <div className="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                          <div className="w-2 h-2 bg-white rotate-45 border-r border-b border-gray-200"></div>
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
