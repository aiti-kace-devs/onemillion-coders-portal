'use client';

import { useState } from 'react';
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
import { pathways } from '../../../data/pathways';
import Button from '../../../components/Button';

export default function PathwayDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('overview');
  
  const pathway = pathways[params.id];

  if (!pathway) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Pathway Not Found</h1>
          <Button onClick={() => router.push('/pathway')} icon={FiArrowLeft}>
            Back to Pathways
          </Button>
        </div>
      </div>
    );
  }

  const tabs = [
    { id: 'overview', label: 'Overview', icon: FiBookOpen },
    { id: 'stories', label: 'Success Stories', icon: FiStar },
    { id: 'faq', label: 'FAQ', icon: FiMessageCircle }
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

              {/* CTA Button */}
              <div className="flex">
                <Button
                  onClick={() => window.open('https://onemillioncoders.gov.gh/available-courses', '_blank')}
                  variant="primary"
                  icon={FiPlay}
                  className="!bg-white !text-gray-900 hover:!bg-gray-100"
                >
                  Start Your Journey
                </Button>
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
                <Image
                  src={pathway.heroImage}
                  alt={pathway.title}
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
                  <h2 className="text-3xl font-bold text-gray-900 mb-6">Pathway Overview</h2>
                  
                  {/* Challenge & Solution */}
                  <div className="space-y-8 mb-12">
                    <div className="bg-red-50 border border-red-200 rounded-xl p-6">
                      <h3 className="text-lg font-semibold text-red-900 mb-3">The Challenge</h3>
                      <p className="text-red-800 leading-relaxed">{pathway.overview.challenge}</p>
                    </div>
                    
                    <div className="bg-green-50 border border-green-200 rounded-xl p-6">
                      <h3 className="text-lg font-semibold text-green-900 mb-3">Our Solution</h3>
                      <p className="text-green-800 leading-relaxed">{pathway.overview.solution}</p>
                    </div>
                  </div>

                  {/* Benefits */}
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
                </div>

                <div className="space-y-6">
                  <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Pathway Details</h3>
                    <div className="space-y-4">
                      <div className="flex justify-between">
                        <span className="text-gray-600">Duration</span>
                        <span className="font-semibold text-gray-900">{pathway.duration}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Focus</span>
                        <span className="font-semibold text-gray-900">{pathway.focus}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Support</span>
                        <span className="font-semibold text-gray-900">Community & Mentorship</span>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200">
                    <div className="flex items-center space-x-2 mb-3">
                      <FiStar className="w-5 h-5 text-yellow-600" />
                      <h3 className="text-lg font-semibold text-gray-900">Why This Pathway?</h3>
                    </div>
                    <p className="text-sm text-gray-700">
                      This pathway is specifically designed for your unique situation, challenges, and goals. 
                      Every element is tailored to help you succeed.
                    </p>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'stories' && (
              <div>
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Success Stories</h2>
                <p className="text-gray-600 mb-8">
                  Real stories from people who followed this pathway and achieved their goals.
                </p>
                
                <div className="grid md:grid-cols-2 gap-8">
                  {pathway.successStories.map((story, index) => (
                    <motion.div
                      key={index}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.4, delay: index * 0.2 }}
                      className="bg-white rounded-xl p-6 shadow-md border border-gray-200"
                    >
                      <div className="flex items-center space-x-3 mb-4">
                        <div className="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full flex items-center justify-center">
                          <FiUsers className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="font-semibold text-gray-900">{story.name}</h3>
                          <p className="text-sm text-gray-600">{story.role}</p>
                        </div>
                      </div>
                      
                      <blockquote className="text-gray-700 italic">
                        &quot;{story.story}&quot;
                      </blockquote>
                    </motion.div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'faq' && (
              <div>
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>
                <div className="space-y-6">
                  {pathway.faqs.map((faq, index) => (
                    <motion.div
                      key={index}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.4, delay: index * 0.1 }}
                      className="bg-white rounded-xl p-6 shadow-md border border-gray-200"
                    >
                      <h3 className="text-lg font-semibold text-gray-900 mb-3">{faq.question}</h3>
                      <p className="text-gray-700 leading-relaxed">{faq.answer}</p>
                    </motion.div>
                  ))}
                </div>
              </div>
            )}
          </motion.div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-gradient-to-r from-yellow-400 to-yellow-500 py-16">
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
                {/* Enroll Now */}
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
      </section>
    </div>
  );
} 