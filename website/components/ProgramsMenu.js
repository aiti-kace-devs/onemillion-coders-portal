"use client";

import { useState } from 'react';
import { motion, AnimatePresence } from "framer-motion";
import {
  FiShield,
  FiDatabase,
  FiCpu,
  FiSmartphone,
  FiServer,
  FiCode,
  FiHeadphones,
  FiGrid,
  FiArrowRight,
} from "react-icons/fi";
import { courses } from "../data/courses";
import { useRouter } from "next/navigation";

const ProgramsMenu = ({ isOpen, onClose }) => {
  const router = useRouter();

  // Map category names to icons
  const iconMap = {
    Cybersecurity: FiShield,
    "DATA Protection": FiDatabase,
    "Artificial Intelligence Training": FiCpu,
    "Mobile Application Development": FiSmartphone,
    "Systems Administration": FiServer,
    "Web Application Programming": FiCode,
    "BPO Training": FiHeadphones,
    "Other Special Training Programs": FiGrid,
  };

  // Get categories from courses data
  const programs = courses.courses.map((category, index) => ({
    id: index + 1,
    title: category.category,
    icon: iconMap[category.category] || FiCode,
  }));

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: 10 }}
          transition={{ duration: 0.2, ease: [0.4, 0, 0.2, 1] }}
          className="absolute top-full left-1/2 transform -translate-x-1/2 mt-3 z-50"
        >
          <div className="bg-white rounded-lg shadow-lg border border-gray-200 p-6 w-[600px]">
            {/* Header */}
            <div className="mb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-1">
                Training Programs
              </h3>
            </div>

            {/* Programs Grid */}
            <div className="grid grid-cols-2 gap-4">
              {programs.map((program) => {
                const IconComponent = program.icon;
                return (
                  <div
                    key={program.id}
                    onClick={() => {
                      router.push(`/programmes?category=${encodeURIComponent(program.title)}`);
                      onClose();
                    }}
                    className="group p-4 rounded-lg hover:bg-gray-50 transition-colors duration-200 cursor-pointer"
                  >
                    <div className="flex items-center space-x-3">
                      <div className="p-2 bg-gray-100 rounded-lg group-hover:bg-gray-200 transition-colors">
                        <IconComponent className="w-5 h-5 text-gray-600" />
                      </div>
                      <div className="flex-1">
                        <div className="flex items-center justify-between">
                          <h4 className="font-medium text-[13px] text-gray-900">
                            {program.title}
                          </h4>
                          <FiArrowRight className="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Footer */}
            <div className="mt-6 pt-4 border-t border-gray-100">
              <div className="flex items-center justify-between">
                <button 
                  onClick={() => {
                    router.push('/course-match');
                    onClose();
                  }}
                  className="text-sm text-yellow-600 underline hover:text-yellow-700 font-medium flex items-center space-x-1"
                >
                  <span>Course Match Help</span>
                  <FiArrowRight className="w-3 h-3" />
                </button>
                <span
                  className="text-sm text-yellow-900 cursor-pointer"
                  onClick={() => {
                    router.push("/programmes");
                    onClose();
                  }}
                >
                  See all programs
                </span>
              </div>
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default ProgramsMenu;
