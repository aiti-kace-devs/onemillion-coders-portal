"use client";

import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import Button from "../../components/Button";
import { useMemo } from "react";

export default function CommunityClient({ data }) {
  // Process API data - support both { data: { sections } } and { sections } response shapes
  const { heroData, storiesData } = useMemo(() => {
    const pageData = data?.data ?? data;
    const sections = pageData?.sections;
    if (!sections || !Array.isArray(sections)) {
      return { heroData: null, storiesData: [] };
    }

    const testimoniesSection = sections.find((s) => s.name === "Testimonies");
    if (!testimoniesSection) {
      return { heroData: null, storiesData: [] };
    }

    const heroData = {
      title: pageData?.title ?? "Our Community",
      caption: testimoniesSection.caption ?? "Real stories from learners transforming their lives through technology",
      description: testimoniesSection.description ?? "Join thousands of Ghanaians who have discovered new opportunities, built successful careers, and are shaping the future of technology in Ghana.",
    };

    const storiesData =
      testimoniesSection.section_items?.map((item) => ({
        id: item.id ?? item.slug,
        name: item.title,
        image: item.image?.url ?? "",
        course: item.profession ?? "",
        story: item.message ?? "",
      })) ?? [];

    return { heroData, storiesData };
  }, [data]);

  if (!data || storiesData.length === 0) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-medium text-gray-900 mb-4">Our Community</h1>
          <p className="text-gray-500">No testimonial data available at the moment.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-6xl mx-auto px-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center max-w-4xl mx-auto"
          >
            <h1 className="text-4xl md:text-5xl font-light text-gray-900 mb-6">
              {heroData.title}
            </h1>
            <p className="text-xl text-gray-600 mb-8 leading-relaxed">
              {heroData.caption}
            </p>
            <p className="text-gray-500 max-w-3xl mx-auto leading-relaxed">
              {heroData.description}
            </p>
          </motion.div>
        </div>
      </section>

      {/* Stories Section */}
      <section className="py-20">
        <div className="max-w-6xl mx-auto px-6">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {storiesData.map((story, index) => (
              <motion.div
                key={story.id}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                className="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300"
              >
                {story.image ? (
                  <div className="aspect-[4/3] relative overflow-hidden">
                    <Image
                      src={story.image}
                      alt={story.name}
                      fill
                      className="object-cover"
                    />
                  </div>
                ) : null}
                <div className="p-6">
                  <h3 className="text-xl font-medium text-gray-900 mb-2">
                    {story.name}
                  </h3>
                  {story.course ? (
                    <p className="text-sm text-gray-500 mb-4">
                      {story.course}
                    </p>
                  ) : null}
                  <p className="text-gray-700 leading-relaxed">
                    {story.story}
                  </p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Call to Action */}
      <section className="py-20 bg-gray-900 text-white">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-3xl font-light mb-6">
              Ready to Write Your Success Story?
            </h2>
            <p className="text-gray-300 text-lg mb-8 leading-relaxed">
              Join thousands of Ghanaians who have transformed their careers through technology.
              Your journey starts with a single step.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href="/programmes">
                <Button size="large" variant="outline">
                  Browse Programs
                </Button>
              </Link>
              <Link href="/course-match">
                <Button size="large">
                  Find Your Course
                </Button>
              </Link>
            </div>
          </motion.div>
        </div>
      </section>
    </div>
  );
}
