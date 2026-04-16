"use client";

import Image from "next/image";
import { motion, useReducedMotion } from "framer-motion";
import { useState, useEffect } from "react";

export default function PartnersSection({ data }) {
  const [isMobile, setIsMobile] = useState(false);
  const prefersReducedMotion = useReducedMotion();

  // Detect mobile device (debounced)
  useEffect(() => {
    let timeoutId;
    const checkMobile = () => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => {
        setIsMobile(window.innerWidth < 768);
      }, 150);
    };

    setIsMobile(window.innerWidth < 768);
    window.addEventListener('resize', checkMobile);
    return () => {
      clearTimeout(timeoutId);
      window.removeEventListener('resize', checkMobile);
    };
  }, []);

  // Use only API data
  const partners = data?.section_items?.map((partner) => ({
    name: partner.title,
    logo: partner.image?.url,
    width: 180,
    height: 80,
    url: partner.link_field,
  })) || [];

  // Don't render if no API data
  if (!data || !partners || partners.length === 0) {
    return null;
  }

  return (
    <section className="py-16 sm:py-24 lg:py-32 bg-gray-900 relative overflow-hidden">
      {/* Dynamic Background */}
      <div className="absolute inset-0">
        <div className="absolute top-0 left-1/4 w-96 h-96 bg-yellow-500/10 rounded-full filter blur-3xl"></div>
        <div className="absolute bottom-0 right-1/4 w-80 h-80 bg-green-500/10 rounded-full filter blur-3xl"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-500/5 rounded-full filter blur-3xl"></div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        {/* Section Header */}
        <div className="text-center mb-16 sm:mb-20 lg:mb-24">
          <motion.div
            initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: prefersReducedMotion ? 0.3 : 0.8 }}
            viewport={{ once: true }}
            className="space-y-4 sm:space-y-6"
          >
            {data?.caption && (
              <div className="inline-block">
                <span className="px-4 py-2 bg-yellow-500/10 text-yellow-400 text-sm font-medium rounded-full border border-yellow-500/20 backdrop-blur-sm">
                  {data.caption}
                </span>
              </div>
            )}
            
            {/* Enhanced Heading */}
            <motion.h2 
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ 
                duration: prefersReducedMotion ? 0.3 : 0.6, 
                delay: prefersReducedMotion ? 0 : 0.2 
              }}
              viewport={{ once: true }}
              className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight"
            >
              Trusted by
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-yellow-500 to-orange-500 mt-2">
                Leading Partners
              </span>
            </motion.h2>
            
            {/* Subtitle */}
            <motion.p
              initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ 
                duration: prefersReducedMotion ? 0.3 : 0.6, 
                delay: prefersReducedMotion ? 0.1 : 0.4 
              }}
              viewport={{ once: true }}
              className="text-base sm:text-lg text-gray-300 max-w-2xl mx-auto leading-relaxed"
            >
              Collaborating with industry leaders and government institutions to build Ghana&apos;s digital future
            </motion.p>
          </motion.div>
        </div>

        {/* Partners Grid */}
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true }}
          variants={{
            hidden: { opacity: 0 },
            visible: {
              opacity: 1,
              transition: {
                duration: prefersReducedMotion ? 0.3 : 0.6,
                delay: prefersReducedMotion ? 0.2 : 0.6,
                staggerChildren: prefersReducedMotion ? 0 : 0.08,
              },
            },
          }}
          className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6 lg:gap-8"
        >
          {partners.map((partner) => (
            <motion.div
              key={partner.name}
              variants={{
                hidden: { opacity: 0, y: prefersReducedMotion ? 0 : 30 },
                visible: {
                  opacity: 1,
                  y: 0,
                  transition: { duration: prefersReducedMotion ? 0.3 : 0.4, ease: "easeOut" },
                },
              }}
              whileHover={prefersReducedMotion ? {} : { y: -8 }}
              className="group"
            >
              <div 
                className={`cursor-pointer relative h-32 sm:h-40 lg:h-48 border rounded-xl lg:rounded-2xl p-4 sm:p-6 lg:p-8 transition-all duration-300 ease-out group-hover:scale-[1.02] ${
                  isMobile
                    ? 'bg-gradient-to-br from-gray-50 via-white to-gray-100 border-gray-200/50 shadow-lg hover:shadow-xl hover:from-yellow-50 hover:via-white hover:to-yellow-50 hover:border-yellow-300/60'
                    : 'backdrop-blur-sm bg-white/10 border-white/20 hover:bg-white/15 hover:border-yellow-500/40 hover:shadow-xl hover:shadow-yellow-500/20'
                }`}
                onClick={() => {
                  if (partner.url) {
                    window.open(partner.url, "_blank");
                  }
                }}
              >
                {/* Enhanced Glow Effect with multiple layers */}
                <div className={`absolute inset-0 rounded-xl lg:rounded-2xl transition-all duration-300 ease-out ${
                  isMobile
                    ? 'bg-gradient-to-br from-yellow-400/8 via-transparent to-blue-400/8 opacity-100 group-hover:from-yellow-400/12 group-hover:to-blue-400/12' // Subtle multi-color glow
                    : 'bg-gradient-to-br from-yellow-500/10 to-transparent opacity-0 group-hover:opacity-100'
                }`}></div>

                {/* Decorative corner elements */}
                {isMobile && (
                  <>
                    <div className="absolute top-2 right-2 w-3 h-3 rounded-full bg-gradient-to-r from-yellow-400 to-orange-400 opacity-20"></div>
                    <div className="absolute bottom-2 left-2 w-2 h-2 rounded-full bg-gradient-to-r from-blue-400 to-green-400 opacity-20"></div>
                  </>
                )}

                {/* Logo Container */}
                <div className="relative h-full flex items-center justify-center">
                  <div className="relative transform transition-transform duration-300 ease-out group-hover:scale-105 z-10">
                    <Image
                      src={partner.logo}
                      alt={partner.name}
                      width={partner.width}
                      height={partner.height}
                      className={`object-contain max-w-full max-h-full transition-all duration-300 ease-out drop-shadow-sm brightness-105 contrast-110 saturate-105 ${
                        !isMobile ? "group-hover:drop-shadow-lg" : ""
                      }`}
                      sizes="(max-width: 640px) 50vw, (max-width: 768px) 33vw, (max-width: 1024px) 25vw, 20vw"
                    />
                  </div>
                </div>

                {/* Subtle border accent */}
                <div className={`absolute inset-0 rounded-xl lg:rounded-2xl transition-all duration-300 pointer-events-none ${
                  isMobile
                    ? 'ring-1 ring-gray-200/50 group-hover:ring-yellow-300/60'
                    : ''
                }`}></div>
              </div>
            </motion.div>
          ))}
        </motion.div>

        {/* Bottom Section */}
        <motion.div
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          transition={{ 
            duration: prefersReducedMotion ? 0.3 : 0.5, 
            delay: prefersReducedMotion ? 0.3 : 0.8 
          }}
          viewport={{ once: true }}
          className="mt-16 sm:mt-20 lg:mt-24 text-center"
        >
          <div className="inline-flex items-center space-x-3 text-gray-400">
            <div className="w-8 sm:w-12 h-px bg-gradient-to-r from-transparent to-gray-600"></div>
            <span className="text-xs sm:text-sm font-medium px-2">
              Building Ghana&apos;s Digital Future Together
            </span>
            <div className="w-8 sm:w-12 h-px bg-gradient-to-l from-transparent to-gray-600"></div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
