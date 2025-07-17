'use client';

import { useState, useEffect } from 'react';
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
  FiStar
} from 'react-icons/fi';
import { courses } from '../../../data/courses';
import Button from '../../../components/Button';

export default function CourseDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('overview');
  
  // Find the course by ID
  const courseId = parseInt(params.id);
  const course = courses.courses
    .flatMap(category => category.jobs.map(job => ({ ...job, category: category.category })))
    .find(job => job.no === courseId);

  // Available courses list
  const availableCourses = [
    'Cybersecurity Officer',
    'IT Support (Networking Focus)',
    'Data Analyst (Microsoft Option)',
    'Data Protection Expert',
    'Data Protection Manager',
    'Data Protection Professional',
    'Data Protection Officer'
  ];

  const isAvailable = course ? availableCourses.includes(course.job_title) : false;

  if (!course) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Course Not Found</h1>
          <Button onClick={() => router.push('/programmes')} icon={FiArrowLeft}>
            Back to Programmes
          </Button>
        </div>
      </div>
    );
  }

  const getCategoryColor = (category) => {
    const colors = {
      'Cybersecurity': 'from-red-400 to-rose-500',
      'DATA Protection': 'from-blue-500 to-blue-600',
      'Artificial Intelligence Training': 'from-purple-500 to-purple-600',
      'Mobile Application Development': 'from-green-500 to-green-600',
      'Systems Administration': 'from-orange-500 to-orange-600',
      'Web Application Programming': 'from-indigo-500 to-indigo-600',
      'BPO Training': 'from-pink-500 to-pink-600',
      'Other Special Training Programs': 'from-gray-500 to-gray-600'
    };
    return colors[category] || 'from-gray-500 to-gray-600';
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
      <section className={`relative py-20 bg-gradient-to-br ${getCategoryColor(course.category)} overflow-hidden`}>
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
            {/* Course Info */}
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              className="text-white"
            >
              {/* Availability Badge */}
              <div className="flex items-center space-x-3 mb-6">
                <span className="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                  {course.category}
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
                {course.job_title}
              </h1>

              {course.training_program && (
                <p className="text-xl text-white/90 mb-6 font-medium">
                  {course.training_program}
                </p>
              )}

              <p className="text-lg text-white/80 mb-8 leading-relaxed">
                {course.job_responsibilities}
              </p>

              {/* Quick Stats */}
              <div className="grid grid-cols-2 gap-6 mb-8">
                {course.training_duration && (
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                      <FiClock className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="font-semibold">{course.training_duration}</div>
                      <div className="text-white/70 text-sm">Duration</div>
                    </div>
                  </div>
                )}

                {course.training_modules && (
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                      <FiBookOpen className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="font-semibold">{course.training_modules.length} Modules</div>
                      <div className="text-white/70 text-sm">Curriculum</div>
                    </div>
                  </div>
                )}
              </div>

              {/* CTA Button */}
              <div className="flex">
                <Button
                  onClick={() => isAvailable && window.open('https://onemillioncoders.gov.gh/available-courses', '_blank')}
                  variant="primary"
                  icon={FiPlay}
                  disabled={!isAvailable}
                  className="!bg-white !text-gray-900 hover:!bg-gray-100"
                >
                  {/* {isAvailable ? 'Enroll Now' : 'Notify When Available'} */}
                  {isAvailable ? 'Register' : 'Notify When Available'}
                </Button>
              </div>
            </motion.div>

            {/* Course Image */}
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="relative"
            >
              <div className="relative h-96 lg:h-[500px] rounded-2xl overflow-hidden shadow-2xl">
                <Image
                  src={course.image || '/images/hero/Certified-Data-Protection-Manager.jpg'}
                  alt={course.job_title}
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
            className="mb-12"
          >
            <div className="border-b border-gray-200">
              <nav className="flex space-x-8">
                {tabs.map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex items-center space-x-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 ${
                      activeTab === tab.id
                        ? 'border-yellow-400 text-yellow-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <tab.icon className="w-4 h-4" />
                    <span>{tab.label}</span>
                  </button>
                ))}
              </nav>
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
              <div className="grid lg:grid-cols-3 gap-12">
                <div className="lg:col-span-2">
                  <h2 className="text-3xl font-bold text-gray-900 mb-6">Course Overview</h2>
                  <div className="prose prose-lg max-w-none">
                    <p className="text-gray-600 leading-relaxed mb-6">
                      {course.job_responsibilities}
                    </p>
                    
                    <h3 className="text-xl font-semibold text-gray-900 mb-4">What You&apos;ll Learn</h3>
                    <ul className="space-y-3">
                      {course.training_modules && course.training_modules.slice(0, 4).map((module, index) => (
                        <li key={index} className="flex items-start space-x-3">
                          <FiCheckCircle className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                          <span className="text-gray-700">{module}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>

                <div className="space-y-6">
                  <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Course Details</h3>
                    <div className="space-y-4">
                      {course.training_duration && (
                        <div className="flex justify-between">
                          <span className="text-gray-600">Duration</span>
                          <span className="font-semibold text-gray-900">{course.training_duration}</span>
                        </div>
                      )}
                      <div className="flex justify-between">
                        <span className="text-gray-600">Modules</span>
                        <span className="font-semibold text-gray-900">
                          {course.training_modules ? course.training_modules.length : 0}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Category</span>
                        <span className="font-semibold text-gray-900">{course.category}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Level</span>
                        <span className="font-semibold text-gray-900">Professional</span>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200">
                    <div className="flex items-center space-x-2 mb-3">
                      <FiStar className="w-5 h-5 text-yellow-600" />
                      <h3 className="text-lg font-semibold text-gray-900">Why Choose This Course?</h3>
                    </div>
                    <ul className="space-y-2 text-sm text-gray-700">
                      <li>• Industry-recognized certification</li>
                      <li>• Hands-on practical training</li>
                      <li>• Expert instructor guidance</li>
                      <li>• Career placement support</li>
                    </ul>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'curriculum' && (
              <div>
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Course Curriculum</h2>
                {course.training_modules && course.training_modules.length > 0 ? (
                  <div className="space-y-4">
                    {course.training_modules.map((module, index) => (
                      <motion.div
                        key={index}
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.4, delay: index * 0.1 }}
                        className="bg-white rounded-xl p-6 shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-300"
                      >
                        <div className="flex items-center space-x-4">
                          <div className="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center text-white font-bold">
                            {index + 1}
                          </div>
                          <div className="flex-1">
                            <h3 className="text-lg font-semibold text-gray-900">{module}</h3>
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
              <div>
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Certifications</h2>
                {course.available_international_certifications && course.available_international_certifications.length > 0 ? (
                  <div className="grid md:grid-cols-2 gap-6">
                    {course.available_international_certifications.map((cert, index) => (
                      <motion.div
                        key={index}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4, delay: index * 0.1 }}
                        className="bg-white rounded-xl p-6 shadow-md border border-gray-200"
                      >
                        <div className="flex items-center space-x-4 mb-4">
                          <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <FiAward className="w-6 h-6 text-white" />
                          </div>
                          <div>
                            <h3 className="text-lg font-semibold text-gray-900">{cert}</h3>
                            <p className="text-sm text-gray-600">International Certification</p>
                          </div>
                        </div>
                        <p className="text-gray-700 text-sm">
                          Industry-recognized certification that validates your skills and expertise.
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
              <div>
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Prerequisites</h2>
                <div className="bg-white rounded-xl p-8 shadow-md border border-gray-200">
                  <div className="flex items-start space-x-4">
                    <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                      <FiCheckCircle className="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h3 className="text-xl font-semibold text-gray-900 mb-4">Entry Requirements</h3>
                      <p className="text-gray-700 leading-relaxed">
                        {course.training_prerequisite || 'No specific prerequisites required. This course is designed for beginners and professionals looking to advance their skills.'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </motion.div>
        </div>
      </section>
    </div>
  );
} 