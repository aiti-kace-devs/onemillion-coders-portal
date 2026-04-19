"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { FiTool, FiArrowLeft } from "react-icons/fi";
import GhanaGradientText from "../../components/GhanaGradients/GhanaGradientText";

export default function HowToRegisterPage() {
  return (
    <main className="min-h-[80vh] bg-gray-50 flex items-center justify-center px-4 py-16">
      <motion.div
        initial={{ opacity: 0, y: 16 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className="max-w-xl w-full text-center bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sm:p-12"
      >
        <div className="mx-auto w-16 h-16 rounded-full bg-yellow-50 flex items-center justify-center mb-6">
          <FiTool className="w-7 h-7 text-yellow-600" />
        </div>

        <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">
          <GhanaGradientText>Page Under Construction</GhanaGradientText>
        </h1>

        <p className="text-gray-600 mb-8">
          We're putting together a clear, step-by-step guide to help you
          register. Please check back soon.
        </p>

        <Link
          href="/"
          className="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900"
        >
          <FiArrowLeft className="w-4 h-4" />
          Back to Home
        </Link>
      </motion.div>
    </main>
  );
}
