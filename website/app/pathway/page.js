'use client';

import { motion } from 'framer-motion';
import Image from 'next/image';
import Link from 'next/link';
import { 
  FiUsers, 
  FiRefreshCw, 
  FiBookOpen, 
  FiHeart, 
  FiPlay,
  FiTrendingUp,
  FiArrowRight,
  FiClock,
  FiTarget
} from 'react-icons/fi';
import { pathways } from '../../data/pathways';
import Button from '../../components/Button';

export default function PathwaysPage() {
  // Icon mapping
  const iconMap = {
    FiUsers,
    FiRefreshCw,
    FiBookOpen,
    FiHeart,
    FiPlay,
    FiTrendingUp
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center"
          >
            <h1 className="text-4xl lg:text-6xl font-bold mb-6">
              Find Your <span className="text-yellow-400">Pathway</span>
            </h1>
            <p className="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
              Every journey is unique. Discover the learning path designed specifically for your background, 
              goals, and circumstances in the One Million Coders program.
            </p>
            
            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">6</div>
                <div className="text-gray-300">Specialized Pathways</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">500K+</div>
                <div className="text-gray-300">Success Stories</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400">100%</div>
                <div className="text-gray-300">Personalized Support</div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Pathways Grid */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center mb-16"
          >
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              Choose Your Journey
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Each pathway is carefully designed with specific challenges, learning approaches, 
              and support systems tailored to your unique situation.
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {Object.values(pathways).map((pathway, index) => {
              const IconComponent = iconMap[pathway.icon];
              
              return (
                <motion.div
                  key={pathway.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  className="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-300"
                >
                  {/* Pathway Image */}
                  <div className="relative h-48 overflow-hidden">
                    <Image
                      src={pathway.heroImage}
                      alt={pathway.title}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    
                    {/* Icon */}
                    <div className="absolute top-6 left-6">
                      <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                        <IconComponent className="w-6 h-6 text-white" />
                      </div>
                    </div>

                    {/* Duration & Focus */}
                    <div className="absolute bottom-6 left-6 right-6">
                      <div className="flex items-center justify-between text-white text-sm">
                        <div className="flex items-center space-x-2">
                          <FiClock className="w-4 h-4" />
                          <span className="font-medium">{pathway.duration}</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <FiTarget className="w-4 h-4" />
                          <span className="font-medium">{pathway.focus}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Content */}
                  <div className="p-8">
                    <h3 className="text-xl font-bold text-gray-900 mb-3 group-hover:text-yellow-600 transition-colors duration-200">
                      {pathway.title}
                    </h3>
                    
                    <p className="text-gray-600 text-sm mb-4 leading-relaxed">
                      {pathway.subtitle}
                    </p>

                    <p className="text-gray-700 text-sm mb-6 line-clamp-3">
                      {pathway.description}
                    </p>

                    {/* Benefits Preview */}
                    <div className="mb-6">
                      <h4 className="text-sm font-semibold text-gray-900 mb-2">Key Benefits:</h4>
                      <ul className="space-y-1">
                        {pathway.overview.benefits.slice(0, 3).map((benefit, idx) => (
                          <li key={idx} className="text-xs text-gray-600 flex items-start">
                            <span className="w-1 h-1 bg-yellow-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                            {benefit}
                          </li>
                        ))}
                      </ul>
                    </div>

                    {/* CTA */}
                    <Link href={`/pathway/${pathway.id}`}>
                      <Button
                        variant="primary"
                        size="small"
                        icon={FiArrowRight}
                        iconPosition="right"
                        className="w-full justify-center"
                      >
                        Explore This Pathway
                      </Button>
                    </Link>
                  </div>
                </motion.div>
              );
            })}
          </div>
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
              Ready to Start Your Tech Journey?
            </h2>
            <p className="text-lg text-gray-800 mb-8">
              Explore our available programs and begin your transformation into Ghana&apos;s tech workforce.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href="/programmes">
                <Button
                  variant="outline"
                  size="large"
                  className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
                >
                  View All Programs
                </Button>
              </Link>
              <Button
                onClick={() => window.open('https://onemillioncoders.gov.gh/available-courses', '_blank')}
                variant="outline"
                size="large"
                className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
              >
                {/* Enroll Now */}
                Register
              </Button>
            </div>
          </motion.div>
        </div>
      </section>
    </div>
  );
} 