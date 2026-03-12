'use client';

import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { useParams, useRouter } from 'next/navigation';
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
  FiLoader
} from 'react-icons/fi';
import { getProgrammeData } from '../../../services/pages';
import Button from '../../../components/Button';
import ProgrammeDetailsSkeleton from '@/components/ProgrammeDetailsSkeleton';
import RegistrationDialog from '@/components/RegistrationDialog';
import { getCourseImage } from '../../../utils/courseImages';

export default function CourseDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('overview');
  const [programme, setProgramme] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showRegistrationDialog, setShowRegistrationDialog] = useState(false);

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
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                  isAvailable 
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

              {/* CTA Button */}
              <div className="flex">
                <Button
                  // onClick={() => isAvailable && setShowRegistrationDialog(true)}
                  onClick={() => {
                    router.push('/register')
                  }}
                  variant="primary"
                  icon={FiPlay}
                  disabled={!isAvailable}
                  className="!bg-white !text-gray-900 hover:!bg-gray-100"
                >
                  {isAvailable ? 'Register Now' : 'Notify When Available'}
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
              <div className="relative h-96 lg:h-[500px] rounded-2xl overflow-hidden shadow-2xl">
                <Image
                  // TEMPORARY: Commented out API image, using static image for consistency
                  // src={programme.image || '/images/hero/Certified-Data-Protection-Manager.jpg'}
                  src={getCourseImage(programme.id)}
                  alt={programme.title}
                  fill
                  className="object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
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
              <nav className="flex md:justify-start overflow-x-auto scrollbar-hide -mb-px">
                <div className="flex space-x-1 md:space-x-8 px-4 md:px-0 min-w-max md:min-w-0">
                  {tabs.map((tab) => (
                    <button
                      key={tab.id}
                      onClick={() => setActiveTab(tab.id)}
                      className={`flex items-center space-x-1.5 md:space-x-2 py-3 md:py-4 px-3 md:px-1 border-b-2 font-medium text-xs md:text-sm transition-colors duration-200 whitespace-nowrap ${
                        activeTab === tab.id
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
            <div className="block md:hidden mt-4 px-4">
              <div className="flex items-center space-x-2 text-sm font-medium text-gray-900">
                {tabs.find(tab => tab.id === activeTab)?.icon && (
                  React.createElement(tabs.find(tab => tab.id === activeTab).icon, {
                    className: "w-4 h-4 text-yellow-600"
                  })
                )}
                <span>{tabs.find(tab => tab.id === activeTab)?.label}</span>
              </div>
            </div>
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
                    <div className="space-y-3 md:space-y-4">
                      {programme.duration && (
                        <div className="flex justify-between items-start">
                          <span className="text-gray-600 text-sm md:text-base">Duration</span>
                          <span className="font-semibold text-gray-900 text-sm md:text-base text-right">{programme.duration}</span>
                        </div>
                      )}
                      <div className="flex justify-between items-start">
                        <span className="text-gray-600 text-sm md:text-base">Modules</span>
                        <span className="font-semibold text-gray-900 text-sm md:text-base text-right">
                          {programme.course_modules_count || 0}
                        </span>
                      </div>
                      <div className="flex justify-between items-start">
                        <span className="text-gray-600 text-sm md:text-base">Category</span>
                        <span className="font-semibold text-gray-900 text-xs md:text-sm text-right max-w-[60%]">{programme.category?.title}</span>
                      </div>
                      <div className="flex justify-between items-start">
                        <span className="text-gray-600 text-sm md:text-base">Level</span>
                        <span className="font-semibold text-gray-900 text-sm md:text-base text-right">{programme.level || 'Professional'}</span>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-4 md:p-6 border border-yellow-200">
                    <div className="flex items-center space-x-2 mb-2 md:mb-3">
                      <FiStar className="w-4 h-4 md:w-5 md:h-5 text-yellow-600 flex-shrink-0" />
                      <h3 className="text-base md:text-lg font-semibold text-gray-900">Why Choose This Programme?</h3>
                    </div>
                    <ul className="space-y-2 text-xs md:text-sm text-gray-700 leading-relaxed">
                      {programme.overview?.why_choose_this_course && programme.overview.why_choose_this_course.length > 0 ? (
                        programme.overview.why_choose_this_course.map((reason, index) => (
                          <li key={index}>• {reason}</li>
                        ))
                      ) : (
                        <>
                          <li>• Industry-recognized certification</li>
                          <li>• Hands-on practical training</li>
                          <li>• Expert instructor guidance</li>
                          <li>• Career placement support</li>
                        </>
                      )}
                    </ul>
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

      {/* Registration Dialog */}
      <RegistrationDialog
        isOpen={showRegistrationDialog}
        onClose={() => setShowRegistrationDialog(false)}
        programme={programme}
      />
    </div>
  );
} 