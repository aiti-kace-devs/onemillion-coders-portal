'use client';

import { motion } from 'framer-motion';
import { FiUsers, FiClock, FiAward, FiBookOpen, FiChevronRight, FiMapPin } from 'react-icons/fi';
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Button from './Button';

const ProgramCard = ({ program }) => {
  const router = useRouter();

  // Get category color
  const getCategoryColor = (category) => {
    const colors = {
      'Cybersecurity': 'bg-red-100 text-red-800 border-red-200',
      'DATA Protection': 'bg-blue-100 text-blue-800 border-blue-200',
      'Artificial Intelligence Training': 'bg-purple-100 text-purple-800 border-purple-200',
      'Mobile Application Development': 'bg-green-100 text-green-800 border-green-200',
      'Systems Administration': 'bg-orange-100 text-orange-800 border-orange-200',
      'Web Application Programming': 'bg-indigo-100 text-indigo-800 border-indigo-200',
      'BPO Training': 'bg-pink-100 text-pink-800 border-pink-200',
      'Other Special Training Programs': 'bg-gray-100 text-gray-800 border-gray-200'
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
        <Image
          src={program.image || '/images/hero/Certified-Data-Protection-Manager.jpg'}
          alt={program.job_title}
          fill
          className="object-cover group-hover:scale-105 transition-transform duration-300"
        />
        
        {/* Category Badge */}
        <div className="absolute top-4 left-4">
          <span className={`px-3 py-1 rounded-full text-xs font-medium border ${getCategoryColor(program.category)}`}>
            {program.category}
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

        {/* Training Duration */}
        {program.training_duration && (
          <div className="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-full px-3 py-1 flex items-center space-x-1">
            <FiClock className="w-3 h-3 text-gray-600" />
            <span className="text-xs font-medium text-gray-700">{program.training_duration}</span>
          </div>
        )}
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