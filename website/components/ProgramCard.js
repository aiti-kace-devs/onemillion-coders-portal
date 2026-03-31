'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { FiClock, FiAward, FiBookOpen, FiChevronRight, FiMapPin, FiGlobe } from 'react-icons/fi';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Button from './Button';

const ProgramCard = ({ program }) => {
  const router = useRouter();
  const [imageError, setImageError] = useState(false);

  // Get category color
  const getCategoryColor = (category) => {
    const colors = {
      'Cybersecurity': 'bg-red-50 text-red-700 border-red-100',
      'Data Protection': 'bg-blue-50 text-blue-700 border-blue-100',
      'Artificial Intelligence': 'bg-purple-50 text-purple-700 border-purple-100',
      'Software Development': 'bg-emerald-50 text-emerald-700 border-emerald-100',
      'Cloud Computing': 'bg-orange-50 text-orange-700 border-orange-100',
      'IT Support': 'bg-indigo-50 text-indigo-700 border-indigo-100',
      'Data Analyst': 'bg-teal-50 text-teal-700 border-teal-100',
      'Digital Marketing': 'bg-pink-50 text-pink-700 border-pink-100',
      'Project Management': 'bg-amber-50 text-amber-700 border-amber-100',
      'UI / UX Design': 'bg-violet-50 text-violet-700 border-violet-100',
      'Digital Literacy': 'bg-cyan-50 text-cyan-700 border-cyan-100',
    };
    return colors[category] || 'bg-gray-100 text-gray-800 border-gray-200';
  };

  return (
    <motion.div
      whileHover={{ y: program.isAvailable ? -5 : 0 }}
      className={`bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden transition-all duration-300 group relative ${
        program.isAvailable ? 'hover:shadow-lg' : 'opacity-75'
      }`}
    >
      {/* Coming Soon Overlay */}
      {!program.isAvailable && (
        <div className="absolute inset-0 bg-gray-50/80 backdrop-blur-[1px] z-10 pointer-events-none"></div>
      )}
      {/* Program Image */}
      <div className="relative h-48 bg-gray-100 overflow-hidden">
        {program.image && !imageError ? (
          <Image
            src={program.image}
            alt={program.job_title}
            fill
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            onError={() => setImageError(true)}
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
        
        {/* Category Badge */}
        <div className="absolute top-4 left-4">
          <span className={`px-3 py-1 rounded-full text-xs font-medium border ${getCategoryColor(program.category?.title || program.category)}`}>
            {program.category?.title || program.category}
          </span>
        </div>

        {/* Availability Badge */}
        {!program.isAvailable && (
          <div className="absolute top-4 left-4 mt-8">
            <span className="px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
              Coming Soon
            </span>
          </div>
        )}

        {/* Training Duration & Mode */}
        <div className="absolute top-4 right-4 flex flex-col gap-1.5 items-end">
          {program.training_duration && (
            <div className="bg-white/90 backdrop-blur-sm rounded-full px-3 py-1 flex items-center space-x-1">
              <FiClock className="w-3 h-3 text-gray-600" />
              <span className="text-xs font-medium text-gray-700">{program.training_duration}</span>
            </div>
          )}
          {program.mode_of_delivery && (
            <div className="bg-white/90 backdrop-blur-sm rounded-full px-3 py-1 flex items-center space-x-1">
              {program.mode_of_delivery === "Online" ? (
                <FiGlobe className="w-3 h-3 text-blue-600" />
              ) : (
                <FiMapPin className="w-3 h-3 text-orange-600" />
              )}
              <span className={`text-xs font-medium ${
                program.mode_of_delivery === "Online" ? "text-blue-700" : "text-orange-700"
              }`}>{program.mode_of_delivery}</span>
            </div>
          )}
        </div>
      </div>

      {/* Card Content */}
      <div className="p-6">
        {/* Job Title */}
        <h3 className="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
          {program.job_title}
        </h3>

        {/* Training Program */}
        {program.training_program && (
          <p className="text-sm text-blue-600 font-medium mb-3">
            {program.training_program}
          </p>
        )}

        {/* Job Responsibilities */}
        <p className="text-gray-600 text-sm mb-4 line-clamp-3">
          {program.job_responsibilities}
        </p>

        {/* Stats */}
        <div className="mb-4">
          <div className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <FiBookOpen className="w-4 h-4 text-green-600" />
            </div>
            <div>
              <div className="text-sm font-semibold text-gray-900">
                {program.training_modules ? program.training_modules.length : 0}
              </div>
              <div className="text-xs text-gray-500">Training Modules</div>
            </div>
          </div>
        </div>

        {/* Prerequisites */}
        {program.training_prerequisite && (
          <div className="mb-4">
            <h4 className="text-xs font-medium text-gray-900 mb-1">Prerequisites:</h4>
            <p className="text-xs text-gray-600 line-clamp-2">{program.training_prerequisite}</p>
          </div>
        )}

        {/* Certifications */}
        {program.available_international_certifications && program.available_international_certifications.length > 0 && (
          <div className="mb-4">
            <div className="flex items-center space-x-1 mb-2">
              <FiAward className="w-3 h-3 text-yellow-600" />
              <span className="text-xs font-medium text-gray-900">Certifications Available</span>
            </div>
            <div className="flex flex-wrap gap-1">
              {program.available_international_certifications.slice(0, 2).map((cert, index) => (
                <span
                  key={index}
                  className="px-2 py-1 bg-yellow-50 text-yellow-700 text-xs rounded border border-yellow-200"
                >
                  {cert}
                </span>
              ))}
              {program.available_international_certifications.length > 2 && (
                <span className="px-2 py-1 bg-gray-50 text-gray-600 text-xs rounded border border-gray-200">
                  +{program.available_international_certifications.length - 2} more
                </span>
              )}
            </div>
          </div>
        )}

        {/* Action Button */}
        <Button
          onClick={() => program.isAvailable && router.push(`/programmes/${program.no}`)}
          variant="outline"
          icon={FiChevronRight}
          className="w-full justify-center"
          disabled={!program.isAvailable}
        >
          {program.isAvailable ? 'Learn More' : 'Coming Soon'}
        </Button>
      </div>
    </motion.div>
  );
};

export default ProgramCard; 