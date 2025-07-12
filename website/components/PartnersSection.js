"use client";

import Image from "next/image";
import { motion } from "framer-motion";

const partners = [
  {
    name: "Ministry of Communications and Digitalisation",
    logo: "/images/partners/moc-logo.png",
    width: 180,
    height: 80,
  },
  {
    name: "Government Partner",
    logo: "/images/partners/download-1.png",
    width: 160,
    height: 70,
  },
  {
    name: "Strategic Partner",
    logo: "/images/partners/download.png",
    width: 160,
    height: 70,
  },
  {
    name: "Technology Partner",
    logo: "/images/partners/download.svg",
    width: 160,
    height: 70,
  },
];

export default function PartnersSection() {
  return (
    <section className="py-32 bg-gray-900 relative overflow-hidden">
      {/* Dynamic Background */}
      <div className="absolute inset-0">
        <div className="absolute top-0 left-1/4 w-96 h-96 bg-yellow-500/10 rounded-full filter blur-3xl animate-pulse"></div>
        <div className="absolute bottom-0 right-1/4 w-80 h-80 bg-green-500/10 rounded-full filter blur-3xl animate-pulse delay-1000"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-500/5 rounded-full filter blur-3xl animate-pulse delay-500"></div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        {/* Section Header */}
        <div className="text-center mb-24">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="space-y-6"
          >
            <div className="inline-block">
              <span className="px-4 py-2 bg-yellow-500/10 text-yellow-400 text-sm font-medium rounded-full border border-yellow-500/20">
                Trusted Partnerships
              </span>
            </div>
            <h2 className="text-5xl md:text-6xl font-bold text-white leading-tight">
              Powered by
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-yellow-500 to-orange-500">
                Excellence
              </span>
            </h2>
          </motion.div>
        </div>

        {/* Partners Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {partners.map((partner, index) => (
            <motion.div
              key={partner.name}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{
                duration: 0.4,
                delay: index * 0.1,
                ease: "easeOut",
              }}
              whileHover={{ y: -8 }}
              className="group"
            >
              <div className="relative h-48 bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8 transition-all duration-300 ease-out hover:bg-white/15 hover:border-yellow-500/40 hover:shadow-xl hover:shadow-yellow-500/20">
                {/* Glow Effect */}
                <div className="absolute inset-0 rounded-2xl bg-gradient-to-br from-yellow-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-out"></div>

                {/* Logo Container */}
                <div className="relative h-full flex items-center justify-center">
                  <div className="transform transition-transform duration-300 ease-out group-hover:scale-105">
                    <Image
                      src={partner.logo}
                      alt={partner.name}
                      width={partner.width}
                      height={partner.height}
                      className="object-contain max-w-full max-h-full brightness-0 invert group-hover:brightness-100 group-hover:invert-0 transition-all duration-300 ease-out"
                    />
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Bottom Section */}
        <motion.div
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          transition={{ duration: 0.5, delay: 0.6 }}
          className="mt-24 text-center"
        >
          <div className="inline-flex items-center space-x-2 text-gray-400">
            <div className="w-12 h-px bg-gradient-to-r from-transparent to-gray-600"></div>
            <span className="text-sm font-medium">
              Building Ghana&apos;s Digital Future Together
            </span>
            <div className="w-12 h-px bg-gradient-to-l from-transparent to-gray-600"></div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
