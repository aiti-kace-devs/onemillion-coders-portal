"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import { FiStar, FiUser } from "react-icons/fi";
import { BiSolidQuoteLeft as FiQuote } from "react-icons/bi";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import { useMemo } from "react";

export default function TestimonialsClient({ data }) {
  // Process API data
  const { testimonialsSection, testimonials } = useMemo(() => {
    if (!data || !data.sections) {
      return { testimonialsSection: null, testimonials: [] };
    }

    // Extract testimonials section
    const section = data.sections.find(s => s.name === "Testimonies");
    const testimonyItems = section?.section_items?.map(item => ({
      id: item.id,
      name: item.title,
      profession: item.profession,
      image: item.image?.url,
      message: item.message,
      slug: item.slug,
    })) || [];

    return { 
      testimonialsSection: section, 
      testimonials: testimonyItems 
    };
  }, [data]);

  // Don't render if no data
  if (!testimonialsSection || testimonials.length === 0) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-medium text-gray-900 mb-4">Testimonials</h1>
          <p className="text-gray-500">No testimonials available at the moment.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <section className="relative py-20 bg-gradient-to-br from-gray-50 to-white overflow-hidden">
        {/* Ghana Map Pattern Background */}
        <div
          className="absolute inset-0 opacity-5"
          style={{
            backgroundImage: "url(/images/ghana/map-pattern.png)",
            backgroundRepeat: "repeat",
            backgroundSize: "400px",
          }}
        />

        <div className="max-w-7xl mx-auto px-6 relative">
          <div className="text-center max-w-4xl mx-auto">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8 }}
            >
              <div className="flex items-center justify-center gap-3 mb-6">
                <Image
                  src="/images/ghana/flag1.png"
                  alt="Ghana Flag"
                  width={40}
                  height={30}
                  className="rounded-sm"
                />
                <span className="text-sm font-medium text-gray-600 uppercase tracking-wider">
                  Success Stories
                </span>
              </div>

              <h1 className="text-4xl md:text-6xl font-light text-gray-900 mb-6 leading-tight">
                {data.title}
                <span className="block text-yellow-600 font-medium">Stories</span>
              </h1>

              {testimonialsSection.caption && (
                <p className="text-xl text-gray-600 mb-4 leading-relaxed">
                  {testimonialsSection.caption}
                </p>
              )}

              {testimonialsSection.description && (
                <p className="text-lg text-gray-500 leading-relaxed max-w-3xl mx-auto">
                  {testimonialsSection.description}
                </p>
              )}
            </motion.div>
          </div>
        </div>
      </section>

      {/* Testimonials Grid */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <motion.div
                key={testimonial.id}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                className="group"
              >
                <div className="bg-white rounded-2xl p-8 shadow-sm border border-gray-200 hover:shadow-xl hover:border-gray-300 transition-all duration-300 h-full flex flex-col">
                  {/* Quote Icon */}
                  <div className="flex items-center justify-between mb-6">
                    <div className="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center">
                      <FiQuote className="w-6 h-6 text-yellow-600" />
                    </div>
                    <div className="flex gap-1">
                      {[...Array(5)].map((_, i) => (
                        <FiStar key={i} className="w-4 h-4 text-yellow-400 fill-current" />
                      ))}
                    </div>
                  </div>

                  {/* Testimonial Message */}
                  <div className="flex-1 mb-6">
                    <p className="text-gray-700 leading-relaxed text-lg italic">
                      &ldquo;{testimonial.message}&rdquo;
                    </p>
                  </div>

                  {/* Profile Section */}
                  <div className="flex items-center gap-4 pt-4 border-t border-gray-100">
                    <div className="relative">
                      {testimonial.image ? (
                        <div className="w-16 h-16 relative rounded-full overflow-hidden bg-gray-100">
                          <Image
                            src={testimonial.image}
                            alt={testimonial.name}
                            fill
                            className="object-cover"
                          />
                        </div>
                      ) : (
                        <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                          <FiUser className="w-8 h-8 text-gray-400" />
                        </div>
                      )}
                    </div>
                    <div className="flex-1">
                      <h3 className="font-semibold text-gray-900 text-lg">
                        {testimonial.name}
                      </h3>
                      <p className="text-sm text-gray-500">
                        {testimonial.profession}
                      </p>
                    </div>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Statistics Section */}
      <section className="py-20 bg-gray-900 text-white relative overflow-hidden">
        {/* Background Elements */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-10 left-10 w-6 h-6">
            <FiStar className="w-full h-full" />
          </div>
          <div className="absolute top-20 right-20 w-4 h-4">
            <FiQuote className="w-full h-full" />
          </div>
          <div className="absolute bottom-10 left-1/3 w-5 h-5">
            <FiUser className="w-full h-full" />
          </div>
        </div>

        <div className="max-w-4xl mx-auto px-6 text-center relative">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl font-light mb-6">
              Join Our Success Community
            </h2>
            <p className="text-xl text-gray-300 mb-8 leading-relaxed">
              These are just a few of the thousands of Ghanaians who have transformed 
              their careers through One Million Coders. Your success story could be next.
            </p>
            
            <div className="grid md:grid-cols-3 gap-8 mb-8">
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400 mb-2">1000+</div>
                <div className="text-gray-300">Success Stories</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400 mb-2">95%</div>
                <div className="text-gray-300">Job Placement Rate</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-yellow-400 mb-2">4.9★</div>
                <div className="text-gray-300">Average Rating</div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Ghana Flag Ribbon Bottom */}
      <GhanaGradientBar height="1px" absolute={false} />
    </div>
  );
} 