'use client';

import { useState, useEffect, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { motion } from 'framer-motion';
import { FiSearch, FiFilter, FiUsers, FiClock, FiAward, FiBookOpen } from 'react-icons/fi';
import { courses } from '../../data/courses';
import ProgramCard from '../../components/ProgramCard';

function ProgrammesPageContent() {
  const searchParams = useSearchParams();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [selectedDuration, setSelectedDuration] = useState('All');
  const [showOnlyAvailable, setShowOnlyAvailable] = useState(false);

  // Handle URL parameters on component mount
  useEffect(() => {
    const categoryParam = searchParams.get('category');
    if (categoryParam) {
      setSelectedCategory(decodeURIComponent(categoryParam));
    }
  }, [searchParams]);

  // Get all categories
  const categories = ['All', ...courses.courses.map(cat => cat.category)];

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

  // Flatten all jobs from all categories and mark availability
  const allPrograms = courses.courses.flatMap(category => 
    category.jobs.map(job => ({
      ...job,
      category: category.category,
      isAvailable: availableCourses.includes(job.job_title)
    }))
  );

  // Filter and sort programs - available courses first
  const filteredPrograms = allPrograms
    .filter(program => {
      const matchesSearch = program.job_title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           program.training_program.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           program.category.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesCategory = selectedCategory === 'All' || program.category === selectedCategory;
      
      const matchesDuration = selectedDuration === 'All' || 
                             (selectedDuration === 'Short' && parseInt(program.training_duration) <= 100) ||
                             (selectedDuration === 'Medium' && parseInt(program.training_duration) > 100 && parseInt(program.training_duration) <= 200) ||
                             (selectedDuration === 'Long' && parseInt(program.training_duration) > 200);

      const matchesAvailability = !showOnlyAvailable || program.isAvailable;

      return matchesSearch && matchesCategory && matchesDuration && matchesAvailability;
    })
    .sort((a, b) => {
      // Sort available courses first, then by program number
      if (a.isAvailable && !b.isAvailable) return -1;
      if (!a.isAvailable && b.isAvailable) return 1;
      return a.no - b.no;
    });

  // Calculate total trainees (handle formula strings)
  const calculateTrainees = (traineeString) => {
    if (!traineeString) return 0;
    if (typeof traineeString === 'string' && traineeString.includes('=')) {
      // Handle formulas like "=4000*5"
      const formula = traineeString.replace('=', '');
      try {
        return eval(formula);
      } catch {
        return 0;
      }
    }
    return parseInt(traineeString) || 0;
  };

  const totalTrainees = allPrograms.reduce((sum, program) => 
    sum + calculateTrainees(program.no_of_people_to_train), 0
  );

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header Section */}
      <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center"
          >
            <h1 className="text-4xl lg:text-6xl font-bold mb-6">
              Training <span className="text-yellow-400">Programmes</span>
            </h1>
            <p className="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
              {courses.program_name}
            </p>
            
            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">{allPrograms.length}</div>
                <div className="text-gray-300">Training Programs</div>
              </div>
              <div className="text-center">
                {/* <div className="text-3xl font-bold text-yellow-400">{totalTrainees.toLocaleString()}+</div> */}
                <div className="text-3xl font-bold text-yellow-400">1M+</div>
                <div className="text-gray-300">Target Trainees</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">{categories.length - 1}</div>
                <div className="text-gray-300">Categories</div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Search and Filters */}
      <section className="py-8 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Filter Header */}
          <div className="text-center mb-8">
            <h2 className="text-2xl font-semibold text-gray-900 mb-2">Find Your Perfect Program</h2>
            <p className="text-gray-600">Use the filters below to discover training programs that match your goals</p>
          </div>

          {/* Filter Controls */}
          <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {/* Search */}
              <div className="lg:col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-2">Search Programs</label>
                <div className="relative">
                  <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    placeholder="Search by title, program, or category..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition-colors duration-200"
                  />
                </div>
              </div>

              {/* Category Filter */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <div className="relative">
                  <FiFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <select
                    value={selectedCategory}
                    onChange={(e) => setSelectedCategory(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition-colors duration-200 appearance-none bg-white"
                  >
                    {categories.map(category => (
                      <option key={category} value={category}>{category}</option>
                    ))}
                  </select>
                  <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </div>
              </div>

              {/* Duration Filter */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                <div className="relative">
                  <FiClock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <select
                    value={selectedDuration}
                    onChange={(e) => setSelectedDuration(e.target.value)}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition-colors duration-200 appearance-none bg-white"
                  >
                    <option value="All">All Durations</option>
                    <option value="Short">Short (≤100 hrs)</option>
                    <option value="Medium">Medium (101-200 hrs)</option>
                    <option value="Long">Long (200+ hrs)</option>
                  </select>
                  <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            {/* Availability Toggle & Results */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-6 pt-6 border-t border-gray-100">
              {/* Availability Filter */}
              <div className="flex items-center space-x-3 mb-4 sm:mb-0">
                <div className="relative">
                  <input
                    type="checkbox"
                    id="availableOnly"
                    checked={showOnlyAvailable}
                    onChange={(e) => setShowOnlyAvailable(e.target.checked)}
                    className="sr-only"
                  />
                  <label
                    htmlFor="availableOnly"
                    className={`flex items-center cursor-pointer px-4 py-2 rounded-full border-2 transition-all duration-200 ${
                      showOnlyAvailable
                        ? 'bg-yellow-400 border-yellow-400 text-gray-900'
                        : 'bg-white border-gray-300 text-gray-700 hover:border-yellow-400'
                    }`}
                  >
                    <div className={`w-4 h-4 mr-2 rounded border-2 flex items-center justify-center ${
                      showOnlyAvailable ? 'bg-gray-900 border-gray-900' : 'border-gray-400'
                    }`}>
                      {showOnlyAvailable && (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      )}
                    </div>
                    <span className="text-sm font-medium">Available Only</span>
                  </label>
                </div>
              </div>

              {/* Results Count */}
              <div className="flex items-center space-x-4">
                <div className="text-sm text-gray-600">
                  <span className="font-semibold text-gray-900">{filteredPrograms.length}</span> of{' '}
                  <span className="font-semibold text-gray-900">{allPrograms.length}</span> programs
                </div>
                {filteredPrograms.length > 0 && (
                  <div className="flex items-center space-x-2 text-sm text-green-600">
                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span>Results found</span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Programs Grid */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {filteredPrograms.length === 0 ? (
            <div className="text-center py-16">
              <div className="flex justify-center mb-4">
                <FiSearch className="w-16 h-16 text-gray-400" />
              </div>
              <h3 className="text-2xl font-semibold text-gray-900 mb-2">No programs found</h3>
              <p className="text-gray-600">Try adjusting your search or filter criteria</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {filteredPrograms.map((program, index) => (
                <motion.div
                  key={program.no}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.05 }}
                >
                  <ProgramCard program={program} />
                </motion.div>
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
}

export default function ProgrammesPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin w-8 h-8 border-4 border-yellow-400 border-t-transparent rounded-full mx-auto mb-4"></div>
        <p className="text-gray-600">Loading programs...</p>
      </div>
    </div>}>
      <ProgrammesPageContent />
    </Suspense>
  );
}