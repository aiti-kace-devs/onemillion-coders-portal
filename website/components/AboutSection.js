"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import Button from "./Button";
import { FiArrowRight } from "react-icons/fi";
import { useRouter } from "next/navigation";
import { GhanaGradientBackground } from "@/components/GhanaGradients";

const AboutSection = () => {
  const router = useRouter();

  const handleLearnMore = () => {
    router.push("/about");
  };

  return (
    <section className="section-spacing bg-gradient-to-br from-gray-50 to-white relative overflow-hidden">
      {/* Ghana Flag Pattern Background */}
`      {/* <div className="absolute inset-0 opacity-3">
        <GhanaGradientBackground size="xs" position="top-left" opacity="20" />
        <div className="absolute bottom-20 right-20 w-48 h-48 rounded-full bg-gradient-to-tl from-green-600 via-yellow-400 to-red-600 blur-3xl"></div>
      </div>` */}
      
      {/* Ghana Flag Ribbon */}
      {/* <div className="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600 opacity-20"></div> */}
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
          {/* Left Content */}
          <motion.div
            initial={{ opacity: 0, x: -50 }}
            whileInView={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8, ease: [0.25, 0.1, 0.25, 1] }}
            viewport={{ once: true }}
            className="space-y-8"
          >
            {/* Section Header */}
            <div className="space-y-4">
              <motion.h2
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: 0.2 }}
                viewport={{ once: true }}
                className="heading-lg text-gray-900 content-spacing"
              >
                The One Million Coders Vision
              </motion.h2>
            </div>

            {/* Content */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.4 }}
              viewport={{ once: true }}
              className="space-y-6"
            >
              <p className="text-lead text-gray-700 element-spacing">
                One Million Coders is transforming Ghana&apos;s digital future by empowering a new generation of tech innovators and leaders. Under the visionary leadership of H.E. John Dramani Mahama, this initiative aims to equip one million Ghanaians with cutting-edge digital skills.
              </p>
              
              <p className="text-lead text-gray-700 element-spacing">
                By joining One Million Coders, you gain access to world-class training programmes, mentorship from industry experts, and a vibrant community of ambitious innovators ready to drive Ghana&apos;s technological advancement and economic growth.
              </p>
              
              <p className="text-lead text-gray-900 font-medium">
                Join us today to be part of Ghana&apos;s digital revolution and help build a prosperous, tech-enabled future for our nation.
              </p>
            </motion.div>

            {/* CTA Button */}
            <motion.div
              initial={{ opacity: 0, y: 20, scale: 0.9 }}
              whileInView={{ opacity: 1, y: 0, scale: 1 }}
              transition={{ duration: 0.6, delay: 0.6 }}
              viewport={{ once: true }}
            >
              <Button
                onClick={handleLearnMore}
                icon={FiArrowRight}
                variant="success"
                size="large"
                iconPosition="right"
                className="font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
              >
                Learn More About Our Vision
              </Button>
            </motion.div>
          </motion.div>

          {/* Right Image */}
          <motion.div
            initial={{ opacity: 0, x: 50 }}
            whileInView={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8, ease: [0.25, 0.1, 0.25, 1] }}
            viewport={{ once: true }}
            className="relative"
          >
            {/* Ghana Star Decoration */}
            <div className="absolute -top-4 -right-4 w-12 h-12 z-10">
              <div className="w-full h-full bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-full flex items-center justify-center shadow-lg">
                <svg className="w-6 h-6 text-black" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
            </div>
            
            {/* Ghana Flag Corner Accent */}
            {/* <div className="absolute top-4 left-4 w-16 h-12 z-10 rounded-lg overflow-hidden shadow-md">
              <div className="w-full h-1/3 bg-red-600"></div>
              <div className="w-full h-1/3 bg-yellow-400 flex items-center justify-center">
                <svg className="w-3 h-3 text-black" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <div className="w-full h-1/3 bg-green-600"></div>
            </div> */}

            {/* Main Image */}
            <div className="aspect-[4/6] relative">
              <Image
                src="/images/jdm-1.png"
                alt="H.E. John Dramani Mahama - President of the Republic of Ghana"
                fill
                className="object-cover rounded-2xl"
                priority
              />
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
};

export default AboutSection; 