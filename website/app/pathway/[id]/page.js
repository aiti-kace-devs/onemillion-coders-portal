'use client';

import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { useParams, useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { 
  FiArrowLeft, 
  FiClock, 
  FiTarget,
  FiCheckCircle,
  FiUsers,
  FiStar,
  FiAward,
  FiBookOpen,
  FiMessageCircle,
  FiArrowRight,
  FiPlay
} from 'react-icons/fi';
// Remove static import
// import { pathways } from '../../../data/pathways';
import Button from '../../../components/Button';
import { getPageData } from '../../../services/api';
import PathwayDetailsSkeleton from '../../../components/PathwayDetailsSkeleton';
import CTASection from '@/components/cta/CTASection';

export default function PathwayDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('overview');
  const [pageData, setPageData] = useState(null);
  const [pathway, setPathway] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        // Fetch the main pathway page data
        const data = await getPageData('pathway');
        setPageData(data);
        
        // Find the specific pathway by slug/id
        const pathwaysSection = data?.sections?.find(section => section.name === 'Pathways');
        const foundPathway = pathwaysSection?.section_items?.find(
          item => item.slug === params.id || item.id === params.id
        );
        
        setPathway(foundPathway);
      } catch (err) {
        console.error('Error fetching pathway data:', err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    if (params.id) {
      fetchData();
    }
  }, [params.id]);

  if (loading) {
    return <PathwayDetailsSkeleton />;
  }

  if (error || !pathway) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            {error ? 'Error Loading Pathway' : 'Pathway Not Found'}
          </h1>
          {error && <p className="text-red-600 mb-4">{error}</p>}
          <Button onClick={() => router.push('/pathway')} icon={FiArrowLeft}>
            Back to Pathways
          </Button>
        </div>
      </div>
    );
  }

  // Extract sections data
  const successStoriesSection = pathway.sections?.find(section => section.title === 'Success Stories');
  const faqsSection = pathway.sections?.find(section => section.title === 'Faqs');
  const successStories = successStoriesSection?.section_items || [];
  const faqs = faqsSection?.section_items || [];

  const tabs = [
    { id: 'overview', label: 'Overview', icon: FiBookOpen },
    { id: 'learning', label: 'Learning Path', icon: FiTarget },
    ...(successStories.length > 0 ? [{ id: 'stories', label: 'Success Stories', icon: FiStar }] : []),
    ...(faqs.length > 0 ? [{ id: 'faq', label: 'FAQ', icon: FiMessageCircle }] : [])
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="relative py-20 bg-gradient-to-br from-gray-900 to-gray-800 overflow-hidden">
        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button */}
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.5 }}
            className="mb-8"
          >
            <Button
              onClick={() => router.push('/pathway')}
              variant="ghost"
              icon={FiArrowLeft}
              iconPosition="left"
              className="!text-white !border-white/30 hover:!bg-white/10"
            >
              Back to Pathways
            </Button>
          </motion.div>

          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Pathway Info */}
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              className="text-white"
            >
              <h1 className="text-4xl lg:text-5xl font-bold mb-6 leading-tight">
                {pathway.title}
              </h1>

              <p className="text-xl text-white/90 mb-6 font-medium">
                {pathway.subtitle}
              </p>

              <p className="text-lg text-white/80 mb-8 leading-relaxed">
                {pathway.description}
              </p>

              {/* Duration & Focus */}
              <div className="flex flex-wrap gap-6 mb-8 text-sm">
                <div className="flex items-center space-x-2">
                  <FiClock className="w-4 h-4 text-yellow-400" />
                  <span className="text-white/90">{pathway.duration}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <FiTarget className="w-4 h-4 text-yellow-400" />
                  <span className="text-white/90">{pathway.focus}</span>
                </div>
              </div>

              {/* CTA Button */}
              <div className="flex">
                <Link href="/register">
                <Button
                  variant="primary"
                  icon={FiPlay}
                  className="!bg-white !text-gray-900 hover:!bg-gray-100"
                  >
                  Start Your Journey
                </Button>
                  </Link>
              </div>
            </motion.div>

            {/* Pathway Image */}
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="relative"
            >
              <div className="relative h-96 lg:h-[500px] rounded-2xl overflow-hidden shadow-2xl">
                {pathway.hero_image && (
                  <Image
                    src={pathway.hero_image.url}
                    alt={pathway.title}
                    fill
                    className="object-cover"
                  />
                )}
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
                  <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Pathway Overview</h2>
                  
                  {/* Challenge & Solution */}
                  {pathway.overview && (
                    <div className="space-y-6 md:space-y-8 mb-8 md:mb-12">
                      {pathway.overview.challenge && (
                        <div className="bg-red-50 border border-red-200 rounded-xl p-4 md:p-6">
                          <h3 className="text-base md:text-lg font-semibold text-red-900 mb-2 md:mb-3">The Challenge</h3>
                          <p className="text-red-800 leading-relaxed text-sm md:text-base">{pathway.overview.challenge}</p>
                        </div>
                      )}
                      
                      {pathway.overview.solution && (
                        <div className="bg-green-50 border border-green-200 rounded-xl p-4 md:p-6">
                          <h3 className="text-base md:text-lg font-semibold text-green-900 mb-2 md:mb-3">Our Solution</h3>
                          <p className="text-green-800 leading-relaxed text-sm md:text-base">{pathway.overview.solution}</p>
                        </div>
                      )}
                    </div>
                  )}

                  {/* Benefits */}
                  {pathway.overview?.benefits && (
                    <div>
                      <h3 className="text-xl font-semibold text-gray-900 mb-6">What You&apos;ll Get</h3>
                      <div className="grid md:grid-cols-2 gap-4">
                        {pathway.overview.benefits.map((benefit, index) => (
                          <div key={index} className="flex items-start space-x-3">
                            <FiCheckCircle className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                            <span className="text-gray-700">{benefit}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>

                <div className="space-y-4 md:space-y-6 lg:mt-0">
                  <div className="bg-white rounded-2xl p-4 md:p-6 shadow-lg border border-gray-200">
                    <h3 className="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4">Pathway Details</h3>
                    <div className="space-y-3 md:space-y-4">
                      <div className="flex justify-between items-start">
                        <span className="text-gray-600 text-sm md:text-base">Duration</span>
                        <span className="font-semibold text-gray-900 text-sm md:text-base text-right">{pathway.duration}</span>
                      </div>
                      <div className="flex justify-between items-start">
                        <span className="text-gray-600 text-sm md:text-base">Focus</span>
                        <span className="font-semibold text-gray-900 text-sm md:text-base text-right">{pathway.focus}</span>
                      </div>
                      {pathway.support?.mentorship && (
                        <div className="flex justify-between items-start">
                          <span className="text-gray-600 text-sm md:text-base">Mentorship</span>
                          <span className="font-semibold text-gray-900 text-xs md:text-sm text-right max-w-[60%]">{pathway.support.mentorship}</span>
                        </div>
                      )}
                      {pathway.support?.community && (
                        <div className="flex justify-between items-start">
                          <span className="text-gray-600 text-sm md:text-base">Community</span>
                          <span className="font-semibold text-gray-900 text-xs md:text-sm text-right max-w-[60%]">{pathway.support.community}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-4 md:p-6 border border-yellow-200">
                    <div className="flex items-center space-x-2 mb-2 md:mb-3">
                      <FiStar className="w-4 h-4 md:w-5 md:h-5 text-yellow-600 flex-shrink-0" />
                      <h3 className="text-base md:text-lg font-semibold text-gray-900">Why This Pathway?</h3>
                    </div>
                    <p className="text-xs md:text-sm text-gray-700 leading-relaxed">
                      This pathway is specifically designed for your unique situation, challenges, and goals. 
                      Every element is tailored to help you succeed.
                    </p>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'learning' && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Learning Path</h2>
                {pathway.learning_path?.phases ? (
                  <div className="space-y-6 md:space-y-8">
                    {pathway.learning_path.phases.map((phase, index) => (
                      <motion.div
                        key={phase.id}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4, delay: index * 0.1 }}
                        className="bg-white rounded-xl p-4 md:p-6 shadow-md border border-gray-200"
                      >
                        <div className="flex items-start space-x-3 md:space-x-4">
                          <div className="flex-shrink-0 w-8 h-8 md:w-10 md:h-10 bg-yellow-500 text-white rounded-full flex items-center justify-center font-bold text-sm md:text-base">
                            {index + 1}
                          </div>
                          <div className="flex-1">
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">{phase.title}</h3>
                            <p className="text-gray-700 mb-4">{phase.description}</p>
                            
                            {phase.modules && (
                              <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-2">Modules:</h4>
                                <ul className="grid md:grid-cols-2 gap-2">
                                  {phase.modules.map((module, moduleIndex) => (
                                    <li key={moduleIndex} className="flex items-start space-x-2 text-sm text-gray-600">
                                      <span className="w-1.5 h-1.5 bg-yellow-500 rounded-full mt-2 flex-shrink-0"></span>
                                      {module}
                                    </li>
                                  ))}
                                </ul>
                              </div>
                            )}
                          </div>
                        </div>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <p className="text-gray-600">Learning path details will be available soon.</p>
                )}
              </div>
            )}

            {activeTab === 'stories' && successStories.length > 0 && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Success Stories</h2>
                <p className="text-gray-600 mb-6 md:mb-8 text-sm md:text-base">
                  Real stories from people who followed this pathway and achieved their goals.
                </p>
                
                <div className="grid md:grid-cols-2 gap-6 md:gap-8">
                  {successStories.map((story, index) => (
                    <motion.div
                      key={story.id}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.4, delay: index * 0.2 }}
                      className="bg-white rounded-xl p-4 md:p-6 shadow-md border border-gray-200"
                    >
                      <div className="flex items-center space-x-3 mb-3 md:mb-4">
                        {story.image?.url ? (
                          <div className="relative w-10 h-10 md:w-12 md:h-12 rounded-full overflow-hidden flex-shrink-0">
                            <Image
                              src={story.image.url}
                              alt={story.title}
                              fill
                              className="object-cover"
                            />
                          </div>
                        ) : (
                          <div className="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <FiUsers className="w-5 h-5 md:w-6 md:h-6 text-white" />
                          </div>
                        )}
                        <div className="min-w-0 flex-1">
                          <h3 className="font-semibold text-gray-900 text-sm md:text-base truncate">{story.title}</h3>
                          <p className="text-xs md:text-sm text-gray-600 truncate">{story.profession}</p>
                        </div>
                      </div>
                      
                      <blockquote className="text-gray-700 italic text-sm md:text-base leading-relaxed">
                        &quot;{story.message}&quot;
                      </blockquote>
                    </motion.div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'faq' && faqs.length > 0 && (
              <div className="px-4 md:px-0">
                <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Frequently Asked Questions</h2>
                <div className="space-y-4 md:space-y-6">
                  {faqs.map((faq, index) => (
                    <motion.div
                      key={faq.id}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.4, delay: index * 0.1 }}
                      className="bg-white rounded-xl p-4 md:p-6 shadow-md border border-gray-200"
                    >
                      <h3 className="text-base md:text-lg font-semibold text-gray-900 mb-2 md:mb-3">{faq.title}</h3>
                      <p className="text-gray-700 leading-relaxed text-sm md:text-base">{faq.answer}</p>
                    </motion.div>
                  ))}
                </div>
              </div>
            )}
          </motion.div>
        </div>
      </section>

      {/* CTA Section */}
      {/* <section className="bg-gradient-to-r from-yellow-400 to-yellow-500 py-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-3xl font-bold text-gray-900 mb-4">
              Ready to Start Your Journey?
            </h2>
            <p className="text-lg text-gray-800 mb-8">
              Join thousands of others who have transformed their careers through this pathway.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button
                onClick={() => window.open('https://onemillioncoders.gov.gh/available-courses', '_blank')}
                variant="outline"
                size="large"
                icon={FiPlay}
                className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
              >
                Register
              </Button>
              <Link href="/programmes">
                <Button
                  variant="outline"
                  size="large"
                  icon={FiArrowRight}
                  className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
                >
                  View All Programs
                </Button>
              </Link>
            </div>
          </motion.div>
        </div>
      </section> */}
      <CTASection />
    </div>
  );
} 