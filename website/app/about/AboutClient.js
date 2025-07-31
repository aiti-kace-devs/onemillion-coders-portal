"use client";
import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import { FiUsers, FiTrendingUp, FiAward, FiBriefcase } from "react-icons/fi";
import Button from "../../components/Button";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import { useMemo } from "react";
import TechGhanaSection from "@/components/TechGhanaSection";

export default function AboutClient({ data }) {
  // Process API data
  const { heroData, metricsData, missionData } = useMemo(() => {
    if (!data || !data.sections) {
      return { heroData: null, metricsData: null, missionData: null };
    }

    // Extract sections
    const heroSection = data.sections.find((s) => s.name === "Hero");
    const hero = heroSection?.section_items?.[0] || null;

    const metricsSection = data.sections.find((s) => s.name === "Metrics");
    const metrics = metricsSection?.section_items?.[0] || null;

    const missionSection = data.sections.find((s) => s.name === "Mission");
    const mission = missionSection?.section_items?.[0] || null;

    return {
      heroData: hero,
      metricsData: metrics,
      missionData: mission,
    };
  }, [data]);

  // Process metrics with icons
  const stats = useMemo(() => {
    if (!metricsData?.metrics) return [];

    const iconMap = [FiUsers, FiBriefcase, FiTrendingUp, FiAward];

    return metricsData.metrics.map((metric, index) => ({
      icon: iconMap[index % iconMap.length],
      value: metric.number,
      label: metric.description.toUpperCase(),
    }));
  }, [metricsData]);

  // Don't render if no data
  if (!heroData) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-medium text-gray-900 mb-4">About</h1>
          <p className="text-gray-500">
            No about data available at the moment.
          </p>
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
                {heroData.about_name}
              </h1>

              {heroData.about_description && (
                <div
                  className="text-xl text-gray-600 mb-8 leading-relaxed"
                  dangerouslySetInnerHTML={{
                    __html: heroData.about_description,
                  }}
                />
              )}

              <div className="flex flex-col sm:flex-row gap-4">
                {heroData.first_button_text && (
                  <Link href={heroData.first_button_link}>
                    <Button
                      size="large"
                      style={{ backgroundColor: heroData.first_button_colour }}
                    >
                      {heroData.first_button_text}
                    </Button>
                  </Link>
                )}
                {heroData.second_button_text && (
                  <Link href={heroData.second_button_link}>
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

            <motion.div
              initial={{ opacity: 0, x: 30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              className="relative"
            >
              <div className="relative rounded-2xl overflow-hidden shadow-2xl">
                <Image
                  src={
                    heroData.about_details_media
                      ? `${process.env.NEXT_PUBLIC_IMAGE_BASE_URL}/${heroData.about_details_media}`
                      : "/images/about/1million1-1.jpg"
                  }
                  alt="One Million Coders Training"
                  width={600}
                  height={400}
                  className="w-full h-auto"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" />
              </div>

              {/* Floating Stats Card */}
              {heroData.metric_value && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.6, delay: 0.8 }}
                  className="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-lg p-6 border border-gray-100"
                >
                  <div className="text-3xl font-light text-gray-900 mb-1">
                    {heroData.metric_value}
                  </div>
                  <div className="text-sm text-gray-500">
                    {heroData.metric_caption}
                  </div>
                </motion.div>
              )}
            </motion.div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      {stats.length > 0 && (
        <section className="py-20 bg-white">
          <div className="max-w-7xl mx-auto px-6">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              viewport={{ once: true }}
              className="text-center mb-16"
            >
              <h2 className="text-3xl font-light text-gray-900 mb-4">
                {metricsData?.text_data_name || "Impact Across Ghana"}
              </h2>
              {metricsData?.text_data_description && (
                <div
                  className="text-gray-500 max-w-2xl mx-auto"
                  dangerouslySetInnerHTML={{
                    __html: metricsData.text_data_description,
                  }}
                />
              )}
            </motion.div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {stats.map((stat, index) => (
                <motion.div
                  key={stat.label}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.6, delay: index * 0.1 }}
                  viewport={{ once: true }}
                  className="text-center p-8 rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-300 bg-white"
                >
                  <div className="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <stat.icon className="w-8 h-8 text-yellow-600" />
                  </div>
                  <div className="text-3xl font-light text-gray-900 mb-2">
                    {stat.value}
                  </div>
                  <div className="text-sm text-gray-500 font-medium">
                    {stat.label}
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Mission Section */}
      {missionData && (
        <section className="py-20 bg-gray-50">
          <div className="max-w-7xl mx-auto px-6">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <motion.div
                initial={{ opacity: 0, x: -30 }}
                whileInView={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.8 }}
                viewport={{ once: true }}
              >
                <h2 className="text-3xl font-light text-gray-900 mb-6">
                  {missionData.title}
                </h2>
                <div
                  className="text-gray-600 leading-relaxed whitespace-pre-line"
                  dangerouslySetInnerHTML={{
                    __html: missionData.content?.replace(/\n/g, "<br/>"),
                  }}
                />
              </motion.div>

              {missionData.images && missionData.images.length > 0 && (
                <motion.div
                  initial={{ opacity: 0, x: 30 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  transition={{ duration: 0.8 }}
                  viewport={{ once: true }}
                  className="grid grid-cols-2 gap-4"
                >
                  <div className="space-y-4">
                    {missionData.images[0] && (
                      <div className="relative rounded-lg overflow-hidden">
                        <Image
                          src={missionData.images[0].url}
                          alt="Training Session"
                          width={300}
                          height={200}
                          className="w-full h-48 object-cover"
                        />
                      </div>
                    )}
                    {missionData.images[1] && (
                      <div className="relative rounded-lg overflow-hidden">
                        <Image
                          src={missionData.images[1].url}
                          alt="Graduation Ceremony"
                          width={300}
                          height={250}
                          className="w-full h-60 object-cover"
                        />
                      </div>
                    )}
                  </div>
                  {missionData.images[2] && (
                    <div className="space-y-4 pt-8">
                      <div className="relative rounded-lg overflow-hidden">
                        <Image
                          src={missionData.images[2].url}
                          alt="Success Stories"
                          width={300}
                          height={300}
                          className="w-full h-72 object-cover"
                        />
                      </div>
                    </div>
                  )}
                </motion.div>
              )}
            </div>
          </div>
        </section>
      )}

      {/* Ghana Flag Ribbon Bottom */}
      <GhanaGradientBar height="1px" absolute={false} />
      <TechGhanaSection />
    </div>
  );
}
