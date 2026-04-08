"use client";

import { motion } from "framer-motion";
import { useMemo } from "react";
import { GhanaGradientBar } from "@/components/GhanaGradients";

export default function TermsClient({ data }) {
  const content = useMemo(() => {
    const raw = data?.data ?? data;
    return raw?.content ?? "";
  }, [data]);

  if (!content) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-medium text-gray-900 mb-4">
            Terms & Conditions
          </h1>
          <p className="text-gray-500">
            No content available at the moment.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <section className="relative py-20 bg-gray-50 overflow-hidden">
        <div
          className="absolute inset-0 opacity-5"
          style={{
            backgroundImage: "url(/images/ghana/map-pattern.png)",
            backgroundRepeat: "repeat",
            backgroundSize: "400px",
          }}
        />
        <div className="max-w-4xl mx-auto px-6 relative">
          <motion.h1
            className="text-4xl md:text-5xl font-light text-gray-900 mb-4"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            Terms & Conditions
          </motion.h1>
          <motion.p
            className="text-lg text-gray-600"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
          >
            Please read the following terms and conditions.
          </motion.p>
        </div>
      </section>

      <section className="py-16 flex-1">
        <div className="max-w-4xl mx-auto px-6">
          <motion.div
            className="prose prose-lg max-w-none text-gray-700 leading-relaxed"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            dangerouslySetInnerHTML={{ __html: content }}
          />
        </div>
      </section>

      <GhanaGradientBar height="1px" absolute={false} />
    </div>
  );
}
