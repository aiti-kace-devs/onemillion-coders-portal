'use client';

import Image from 'next/image';
import { motion } from 'framer-motion';
import { FiClock, FiUsers, FiArrowRight } from 'react-icons/fi';
import { courses } from '../data/courses';
import Button from './Button';
import { GhanaGradientBar } from '@/components/GhanaGradients';
import { useState } from 'react';
import { useRouter } from 'next/navigation';

const CoursesSection = () => {
  const [selectedCategory, setSelectedCategory] = useState('All');
  const router = useRouter();
  // Define the specific courses to display
  const featuredCoursePrograms = [
    'Certified Data Protection Practitioner – CDPP',
    'Certified Data Protection Officer – CDPO',
    'Certified Data Protection Expert – CDPE',
    'Certified Data Protection Supervisor – CDPS',
    'Data Analyst Associate',
    'Certified Cybersecurity Professional'
  ];

  // Find courses that match the featured programs
  const featuredCourses = [];
  courses.courses.forEach(category => {
    category.jobs.forEach(job => {
      if (featuredCoursePrograms.includes(job.training_program) && job.image) {
        featuredCourses.push({
          id: job.no,
          title: job.training_program,
          description: job.job_responsibilities,
          duration: job.training_duration,
          trainees: job.no_of_people_to_train,
          image: job.image,
          category: category.category,
          prerequisites: job.training_prerequisite,
          certifications: job.available_international_certifications
        });
      }
    });
  });

  // Get unique categories for tags
  const categories = ['All', ...new Set(featuredCourses.map(course => course.category))];

  // Filter courses based on selected category
  const filteredCourses = selectedCategory === 'All' 
    ? featuredCourses 
    : featuredCourses.filter(course => course.category === selectedCategory);

  // Category color mapping
  const categoryColors = {
    'Cybersecurity': 'bg-red-100 text-red-800 border-red-200',
    'DATA Protection': 'bg-blue-100 text-blue-800 border-blue-200',
    'Artificial Intelligence Training': 'bg-purple-100 text-purple-800 border-purple-200',
    'Mobile Application Development': 'bg-green-100 text-green-800 border-green-200',
    'Systems Administration': 'bg-orange-100 text-orange-800 border-orange-200',
    'Web Application Programming': 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'BPO Training': 'bg-pink-100 text-pink-800 border-pink-200',
    'Other Special Training Programs': 'bg-gray-100 text-gray-800 border-gray-200'
  };

  return (
    <section className="bg-gray-100 relative overflow-hidden">
      <div className="section-spacing relative">
        {/* Ghana Map Pattern Background */}
        <div className="absolute inset-0 opacity-8">
          <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-gray-400/10 rounded-full blur-3xl"></div>
          
          {/* Ghana Map Dot Pattern */}
          <div className="absolute top-20 right-20 w-64 h-64 opacity-20">
            <div className="w-full h-full" style={{
              background: `radial-gradient(circle, #10b981 1px, transparent 1px)`,
              backgroundSize: '12px 12px'
            }}></div>
          </div>
          
          {/* Ghana Flag Ribbon */}
          <GhanaGradientBar height="1px" position="bottom-full" opacity="30" />
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          {/* Section Header */}
          <div className="mb-16">
            <motion.h2 
              initial={{ opacity: 0, y: 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4 }}
              className="heading-lg text-gray-900 content-spacing"
            >
              Professional Courses
            </motion.h2>
            <motion.p 
              initial={{ opacity: 0, y: 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.05 }}
              className="text-lead text-gray-600 max-w-4xl content-spacing"
            >
              Advance your career with our comprehensive professional courses designed for industry leaders. 
              Gain specialized expertise and earn globally recognized certifications.
            </motion.p>

            {/* Category Tags */}
            <motion.div 
              initial={{ opacity: 0, y: 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.1 }}
              className="flex flex-wrap gap-4 mb-12"
            >
              {categories.map((category, index) => (
                <motion.button
                  key={category}
                  initial={{ opacity: 0, scale: 0.9 }}
                  whileInView={{ opacity: 1, scale: 1 }}
                  transition={{ duration: 0.2, delay: 0.02 * index }}
                  onClick={() => setSelectedCategory(category)}
                  className={`px-6 py-3 rounded-full text-sm font-semibold border hover:scale-[1.02] transition-all duration-150 cursor-pointer ${
                    selectedCategory === category
                      ? 'bg-yellow-500 text-gray-900 border-yellow-500'
                      : category === 'All'
                      ? 'bg-gray-200 text-gray-800 border-gray-300 hover:bg-gray-300'
                      : categoryColors[category] || 'bg-gray-100 text-gray-800 border-gray-200'
                  }`}
                >
                  {category}
                </motion.button>
              ))}
            </motion.div>
          </div>

          {/* Courses Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredCourses.map((course, index) => (
              <motion.div
                key={course.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3, delay: index * 0.05 }}
                className="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-200"
              >
                {/* Course Image */}
                <div className="relative h-56 overflow-hidden">
                  <Image
                    src={course.image}
                    alt={course.title}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                  <div className="absolute top-6 left-6">
                    <span className="px-4 py-2 rounded-full text-xs font-bold bg-yellow-500 text-gray-900 shadow-lg">
                      {course.category}
                    </span>
                  </div>
                  <div className="absolute bottom-6 left-6 right-6">
                    <div className="flex items-center space-x-4 text-white text-sm">
                      <div className="flex items-center space-x-2">
                        <FiClock className="w-4 h-4" />
                        <span className="font-medium">{course.duration}</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <FiUsers className="w-4 h-4" />
                        <span className="font-medium">
                          {(() => {
                            if (typeof course.trainees === 'string') {
                              const cleanValue = course.trainees.replace('=', '');
                              if (cleanValue.includes('*')) {
                                const [num1, num2] = cleanValue.split('*').map(n => parseInt(n.trim()));
                                return ((num1 || 0) * (num2 || 0)).toLocaleString();
                              } else {
                                return (parseInt(cleanValue) || 0).toLocaleString();
                              }
                            } else {
                              return (course.trainees || 0).toLocaleString();
                            }
                          })()}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Course Content */}
                <div className="p-8">
                  <h3 className="text-xl font-bold text-gray-900 mb-4 line-clamp-2 group-hover:text-yellow-600 transition-colors duration-150">
                    {course.title}
                  </h3>
                  
                  <p className="text-gray-600 text-sm mb-6 line-clamp-3 leading-relaxed">
                    {course.description}
                  </p>

                  {/* CTA Button */}
                  <Button
                    variant="primary"
                    size="small"
                    icon={FiArrowRight}
                    iconPosition="right"
                    className="w-full justify-center"
                    onClick={() => router.push(`/programmes/${course.id}`)}
                  >
                    Learn More
                  </Button>
                </div>
              </motion.div>
            ))}
          </div>

          {/* View All Courses CTA */}
          <div className="text-center mt-16">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.3 }}
            >
              <Button
                variant="outline"
                size="large"
                icon={FiArrowRight}
                iconPosition="right"
                onClick={() => router.push("/programmes")}
              >
                Explore Our Full Range of Programmes
              </Button>
            </motion.div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default CoursesSection; 