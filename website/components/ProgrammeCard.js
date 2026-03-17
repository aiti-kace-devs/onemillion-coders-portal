"use client";

import Image from "next/image";
import { useRouter } from "next/navigation";
import { FiClock, FiUsers, FiArrowRight } from "react-icons/fi";
import Button from "./Button";
import { getCourseImage } from "../utils/courseImages";

const ProgrammeCard = ({ programme }) => {
  const router = useRouter();

  // TEMPORARY: Use static image instead of API image for consistency
  const courseImage = getCourseImage(programme.id);

  // Category color mapping
  const categoryColors = {
    Cybersecurity: "bg-red-100 text-red-800 border-red-200",
    "DATA Protection": "bg-blue-100 text-blue-800 border-blue-200",
    "Data Protection": "bg-blue-100 text-blue-800 border-blue-200",
    "Artificial Intelligence Training": "bg-purple-100 text-purple-800 border-purple-200",
  };

  return (
    <div 
      className="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-200 cursor-pointer group"
    >
      {/* Image Container */}
      <div className="relative w-full h-48">
        <Image
          // TEMPORARY: Commented out API image, using static image for consistency
          // src={programme.image}
          src={courseImage}
          alt={programme.title}
          fill
          className="object-cover group-hover:scale-105 transition-transform duration-200"
        />
        {/* Category Tag Overlay */}
        <div className="absolute top-4 left-4">
          <span 
            onClick={(e) => {
              e.stopPropagation();
              // Only navigate to category page if we're already on the programmes page
              if (window.location.pathname.startsWith('/programmes')) {
                router.push(`/programmes/category/${programme.category?.id}`);
              }
            }}
            className={`px-3 py-1 rounded-full text-xs font-medium border cursor-pointer hover:shadow-md transition-shadow ${
              categoryColors[programme.category?.title] || "bg-gray-100 text-gray-800 border-gray-200"
            }`}
          >
            {programme.category?.title}
          </span>
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        {/* Title */}
        <h3 className="text-xl font-semibold text-gray-900 mb-2 line-clamp-1">{programme.title}</h3>
        <p className="text-sm text-gray-600 mb-4 line-clamp-1">{programme.sub_title}</p>

        {/* Stats */}
        <div className="flex items-center justify-between text-sm text-gray-600 mb-4">
          <div className="flex items-center space-x-2">
            <FiClock className="w-4 h-4" />
            <span>{programme.duration}</span>
          </div>
          <div className="flex items-center space-x-2">
            {programme.mode_of_delivery && (
              <span className="flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">
                <FiGlobe className="w-3 h-3" />
                {programme.mode_of_delivery}
              </span>
            )}
            <span className="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
              {programme.level}
            </span>
          </div>
        </div>

        {/* Description */}
        <p className="text-gray-600 text-sm mb-4 line-clamp-2">
          {programme.job_responsible}
        </p>

        {/* Learn More Button */}
        <Button
          variant="primary"
          size="small"
          icon={FiArrowRight}
          iconPosition="right"
          className="w-full justify-center group-hover:shadow-lg transition-all duration-200"
          onClick={(e) => {
            e.stopPropagation();
            router.push(`/programmes/${programme.id}`);
          }}
        >
          Learn More
        </Button>
      </div>
    </div>
  );
};

export default ProgrammeCard; 