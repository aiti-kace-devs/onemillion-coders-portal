"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import {
  FiUsers,
  FiTrendingUp,
  FiAward,
  FiGlobe,
  FiTarget,
  FiBookOpen,
  FiCode,
  FiShield,
  FiBriefcase,
} from "react-icons/fi";
import Button from "../../components/Button";
import TechGhanaSection from "../../components/TechGhanaSection";
import { GhanaGradientBar } from "@/components/GhanaGradients";

export default function AboutPage() {
  const stats = [
    { icon: FiUsers, value: "306K", label: "GRADUATES" },
    { icon: FiBriefcase, value: "138,693", label: "YOUTH IN WORK" },
    { icon: FiTrendingUp, value: "21,726", label: "YOUTH STARTING VENTURES" },
    {
      icon: FiAward,
      value: "70,553",
      label: "YOUTH IN JOBS CREATED THROUGH ENTREPRENEURSHIP",
    },
  ];

  const programs = [
    {
      icon: FiShield,
      title: "Cybersecurity",
      description:
        "Protecting Ghana's digital infrastructure through world-class security training",
    },
    {
      icon: FiCode,
      title: "Software Development",
      description:
        "Building the next generation of Ghanaian software engineers and developers",
    },
    {
      icon: FiBookOpen,
      title: "Data Analytics",
      description: "Empowering data-driven decision making across industries",
    },
    {
      icon: FiTarget,
      title: "Digital Skills",
      description:
        "Essential digital literacy for the modern Ghanaian workforce",
    },
  ];

  const milestones = [
    {
      year: "2019",
      title: "Programme Launch",
      description:
        "One Million Coders initiative officially launched by the Government of Ghana",
    },
    {
      year: "2020",
      title: "First Cohort",
      description:
        "Successfully trained and deployed first 10,000 coders across Ghana",
    },
    {
      year: "2022",
      title: "Regional Expansion",
      description: "Extended training programs to all 16 regions of Ghana",
    },
    {
      year: "2024",
      title: "Half Million Milestone",
      description:
        "Reached 500,000+ trained professionals with 85% employment rate",
    },
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Ghana Flag Ribbon */}
      {/* <div className="h-1 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600"></div> */}

      {/* Hero Section */}
      <section className="relative py-20 bg-gray-50 overflow-hidden">
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
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8 }}
            >
              <div className="flex items-center gap-3 mb-6">
                <Image
                  src="/images/ghana/flag1.png"
                  alt="Ghana Flag"
                  width={40}
                  height={30}
                  className="rounded-sm"
                />
                <span className="text-sm font-medium text-gray-600 uppercase tracking-wider">
                  Proudly Ghanaian
                </span>
              </div>

              <h1 className="text-4xl md:text-6xl font-light text-gray-900 mb-6 leading-tight">
                One Million
                <span className="block text-yellow-600 font-medium">
                  Coders
                </span>
              </h1>

              <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                Transforming Ghana&apos;s digital future by training one million
                young Ghanaians in cutting-edge technology skills, creating
                opportunities, and building a world-class digital economy.
              </p>

              <div className="flex flex-col sm:flex-row gap-4">
                <Link href="/programmes">
                  <Button size="large">Explore Programs</Button>
                </Link>
                <Link href="/community">
                  <Button size="large" variant="outline">
                    Success Stories
                  </Button>
                </Link>
              </div>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              className="relative"
            >
              <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                <Image
                  src="/images/about/1million1-1.jpg"
                  alt="One Million Coders Training"
                  width={600}
                  height={400}
                  className="w-full h-auto"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" />
              </div>

              {/* Floating Stats Card */}
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: 0.8 }}
                className="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-lg p-6 border border-gray-100"
              >
                <div className="text-3xl font-light text-gray-900 mb-1">
                  500,000+
                </div>
                <div className="text-sm text-gray-500">Lives Transformed</div>
              </motion.div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center mb-16"
          >
            <h2 className="text-3xl font-light text-gray-900 mb-4">
              Impact Across Ghana
            </h2>
            <p className="text-gray-500 max-w-2xl mx-auto">
              Real numbers, real impact, real transformation across all regions
              of Ghana
            </p>
          </motion.div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {stats.map((stat, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                className="text-center p-8 rounded-2xl border border-gray-100 hover:border-gray-200 hover:shadow-md transition-all duration-300"
              >
                <div className="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6">
                  <stat.icon className="w-7 h-7 text-yellow-600" />
                </div>
                <div className="text-3xl font-light text-gray-900 mb-2">
                  {stat.value}
                </div>
                <div className="text-gray-500">{stat.label}</div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Mission Section */}
      <section className="py-20 bg-gray-50 relative overflow-hidden">
        {/* Ghana Flag Pattern Background */}
        <div
          className="absolute top-0 right-0 w-1/3 h-full opacity-10"
          style={{
            backgroundImage: "url(/images/ghana/flag-bar.png)",
            backgroundRepeat: "no-repeat",
            backgroundSize: "cover",
            backgroundPosition: "center",
          }}
        />

        <div className="max-w-7xl mx-auto px-6 relative">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <motion.div
              initial={{ opacity: 0, x: -30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8 }}
            >
              <h2 className="text-4xl font-light text-gray-900 mb-6">
                Our Mission
              </h2>
              <p className="text-lg text-gray-700 mb-6 leading-relaxed">
                To bridge Ghana&apos;s digital skills gap by providing
                world-class technology training to one million young Ghanaians,
                creating sustainable employment opportunities and positioning
                Ghana as a leading digital economy in Africa.
              </p>
              <p className="text-gray-600 leading-relaxed">
                Through strategic partnerships with industry leaders, government
                support, and innovative training methodologies, we&apos;re not
                just teaching coding – we&apos;re building Ghana&apos;s digital
                future, one coder at a time.
              </p>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              className="relative"
            >
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-4">
                  <div className="rounded-xl overflow-hidden">
                    <Image
                      src="/images/about/1million-5.jpg"
                      alt="Training Session"
                      width={300}
                      height={200}
                      className="w-full h-48 object-cover"
                    />
                  </div>
                  <div className="rounded-xl overflow-hidden">
                    <Image
                      src="/images/about/our-mission-2.jpg"
                      alt="Graduation Ceremony"
                      width={300}
                      height={250}
                      className="w-full h-56 object-cover"
                    />
                  </div>
                </div>
                <div className="pt-8">
                  <div className="rounded-xl overflow-hidden">
                    <Image
                      src="/images/about/our-mission-1.jpg"
                      alt="Success Story"
                      width={300}
                      height={300}
                      className="w-full h-64 object-cover"
                    />
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Programs Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center mb-16"
          >
            <h2 className="text-3xl font-light text-gray-900 mb-4">
              Training Programs
            </h2>
            <p className="text-gray-500 max-w-2xl mx-auto">
              Comprehensive programs designed to meet industry demands and
              create immediate employment opportunities
            </p>
          </motion.div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {programs.map((program, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                className="p-8 rounded-2xl border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300"
              >
                <div className="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center mb-6">
                  <program.icon className="w-6 h-6 text-yellow-600" />
                </div>
                <h3 className="text-xl font-medium text-gray-900 mb-3">
                  {program.title}
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  {program.description}
                </p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Timeline Section - Commented Out */}
      {/* 
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center mb-16"
          >
            <h2 className="text-3xl font-light text-gray-900 mb-4">
              Our Journey
            </h2>
            <p className="text-gray-500 max-w-2xl mx-auto">
              Key milestones in building Ghana's largest technology skills training program
            </p>
          </motion.div>

          <div className="relative">
            <div className="absolute left-1/2 transform -translate-x-1/2 w-px h-full bg-gray-200"></div>
            
            <div className="space-y-12">
              {milestones.map((milestone, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 30 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.6, delay: index * 0.2 }}
                  className={`flex items-center ${index % 2 === 0 ? &apos;justify-start&apos; : &apos;justify-end&apos;}`}
                >
                                      <div className={`w-5/12 ${index % 2 === 0 ? &apos;text-right pr-8&apos; : &apos;text-left pl-8&apos;}`}>
                    <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                      <div className="text-yellow-600 font-medium mb-2">{milestone.year}</div>
                      <h3 className="text-lg font-medium text-gray-900 mb-2">{milestone.title}</h3>
                      <p className="text-gray-600">{milestone.description}</p>
                    </div>
                  </div>
                  
                  <div className="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-yellow-400 rounded-full border-4 border-white shadow-sm"></div>
                </motion.div>
              ))}
            </div>
          </div>
        </div>
      </section>
      */}

      {/* TechGhana Map Section */}
      <TechGhanaSection />

      {/* CTA Section */}
      {/* <section className="py-20 bg-gray-900 text-white relative overflow-hidden">
        <div className="absolute inset-0 opacity-5">
          <div className="absolute top-10 left-10 w-6 h-6">
            <svg className="w-full h-full fill-current" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <div className="absolute top-20 right-20 w-4 h-4">
            <svg className="w-full h-full fill-current" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <div className="absolute bottom-10 left-1/3 w-5 h-5">
            <svg className="w-full h-full fill-current" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
        </div>

        <div className="max-w-4xl mx-auto px-6 text-center relative">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-4xl font-light mb-6">
              Join Ghana's Digital Revolution
            </h2>
            <p className="text-xl text-gray-300 mb-8 leading-relaxed">
              Be part of the movement that's transforming Ghana into Africa's leading 
              digital economy. Your journey to a successful tech career starts here.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href="/course-match">
                <Button size="large">
                  Find Your Course
                </Button>
              </Link>
              <Link href="/programmes">
                <Button size="large" variant="outline">
                  View All Programs
                </Button>
              </Link>
            </div>
          </motion.div>
        </div>
      </section> */}

      {/* Ghana Flag Ribbon Bottom */}
      <GhanaGradientBar height="1px" absolute={false} />
    </div>
  );
}
