"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import {
  FiUsers,
  FiBookOpen,
  FiMapPin,
  FiUserCheck,
  FiCode,
  FiShield,
  FiSmartphone,
  FiMonitor,
  FiTrendingUp,
  FiGlobe,
  FiAward,
  FiCpu,
  FiPlay,
  FiCheck,
  FiExternalLink,
} from "react-icons/fi";
import Button from "../../components/Button";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import Accordion from "../../components/Accordion";
import { useState, useMemo } from "react";
import CTASection from "@/components/cta/CTASection";

// Icon mapping for different FAQ categories
const categoryIcons = {
  About: FiUsers,
  Eligibility: FiUserCheck,
  Courses: FiBookOpen,
  Training: FiMapPin,
  Registration: FiExternalLink,
  Default: FiCode,
};

export default function FaqsClient({ data }) {
  const [activeCategory, setActiveCategory] = useState("All");

  // Process API data
  const { heroData, faqsData } = useMemo(() => {
    if (!data || !data.sections) {
      return { heroData: null, faqsData: [] };
    }

    // console.log(data);

    // Extract hero section
    const heroSection = data.sections.find((s) => s.name === "Hero");
    const hero = heroSection?.section_items?.[0] || null;

    // Extract FAQs section
    const faqsSection = data.sections.find((s) => s.name === "faqs");
    const faqs =
      faqsSection?.section_items?.map((item) => ({
        id: item.slug || item.id,
        question: item.title,
        answer: item.answer,
        category: item.tag?.[0] || "General",
        icon: categoryIcons[item.tag?.[0]] || categoryIcons.Default,
      })) || [];

    return { heroData: hero, faqsData: faqs };
  }, [data]);

  // Generate categories from FAQ data
  const categories = useMemo(() => {
    const cats = new Set(["All"]);
    faqsData.forEach((faq) => cats.add(faq.category));
    return Array.from(cats);
  }, [faqsData]);

  const filteredFaqs =
    activeCategory === "All"
      ? faqsData
      : faqsData.filter((faq) => faq.category === activeCategory);

  // Don't render if no data
  if (!data || !heroData || faqsData.length === 0) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-medium text-gray-900 mb-4">FAQs</h1>
          <p className="text-gray-500">No FAQ data available at the moment.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
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
                  Frequently Asked Questions
                </span>
              </div>

              <h1 className="text-4xl md:text-6xl font-light text-gray-900 mb-6 leading-tight">
                {heroData.about_name || "One Million Coders FAQs"}
              </h1>

              {heroData.about_description && (
                <div
                  className="text-xl text-gray-600 mb-8 leading-relaxed"
                  dangerouslySetInnerHTML={{
                    __html: heroData.about_description,
                  }}
                />
              )}

              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                {heroData.first_button_text && (
                  <Link href={heroData.first_button_link || "/programmes"}>
                    <Button
                      size="large"
                      style={{ backgroundColor: heroData.first_button_colour }}
                    >
                      {heroData.first_button_text}
                    </Button>
                  </Link>
                )}
                {heroData.second_button_text && (
                  <Link href={heroData.second_button_link || "/course-match"}>
                    <Button
                      size="large"
                      variant="outline"
                      style={{
                        borderColor: heroData.second_button_colour,
                        color: heroData.second_button_colour,
                      }}
                    >
                      {heroData.second_button_text}
                    </Button>
                  </Link>
                )}
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* FAQ Categories Filter */}
      {categories.length > 1 && (
        <section className="py-8 bg-gray-50">
          <div className="max-w-7xl mx-auto px-6">
            <div className="flex flex-wrap justify-center gap-2">
              {categories.map((category) => (
                <motion.button
                  key={category}
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ duration: 0.3 }}
                  onClick={() => setActiveCategory(category)}
                  className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 ${
                    activeCategory === category
                      ? "bg-yellow-400 text-gray-900 shadow-md"
                      : "bg-white text-gray-600 hover:bg-gray-100 border border-gray-200"
                  }`}
                >
                  {category}
                </motion.button>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* FAQ Section */}
      <section className="py-20 bg-white">
        <div className="max-w-4xl mx-auto px-6">
          <Accordion items={filteredFaqs} />
        </div>
      </section>

      <CTASection/>

      {/* Ghana Flag Ribbon Bottom */}
      <GhanaGradientBar height="1px" absolute={false} />
    </div>
  );
}
