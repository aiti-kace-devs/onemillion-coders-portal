"use client";

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

import { useRouter } from "next/navigation";

const ProgramsMenu = ({ isOpen, onClose, categories = [], isLoading = false }) => {
  const router = useRouter();

  // Map category names to icons and colors
  const categoryStyleMap = {
    Cybersecurity: { icon: FiShield, color: "text-red-500", bg: "bg-red-50", hoverBg: "hover:bg-red-50", border: "group-hover:border-red-200" },
    "DATA Protection": { icon: FiDatabase, color: "text-blue-500", bg: "bg-blue-50", hoverBg: "hover:bg-blue-50", border: "group-hover:border-blue-200" },
    "Data Protection": { icon: FiDatabase, color: "text-blue-500", bg: "bg-blue-50", hoverBg: "hover:bg-blue-50", border: "group-hover:border-blue-200" },
    "Artificial Intelligence Training": { icon: FiCpu, color: "text-purple-500", bg: "bg-purple-50", hoverBg: "hover:bg-purple-50", border: "group-hover:border-purple-200" },
    "Mobile Application Development": { icon: FiSmartphone, color: "text-green-500", bg: "bg-green-50", hoverBg: "hover:bg-green-50", border: "group-hover:border-green-200" },
    "Systems Administration": { icon: FiServer, color: "text-orange-500", bg: "bg-orange-50", hoverBg: "hover:bg-orange-50", border: "group-hover:border-orange-200" },
    "Web Application Programming": { icon: FiCode, color: "text-indigo-500", bg: "bg-indigo-50", hoverBg: "hover:bg-indigo-50", border: "group-hover:border-indigo-200" },
    "BPO Training": { icon: FiHeadphones, color: "text-teal-500", bg: "bg-teal-50", hoverBg: "hover:bg-teal-50", border: "group-hover:border-teal-200" },
    "Other Special Training Programs": { icon: FiGrid, color: "text-amber-500", bg: "bg-amber-50", hoverBg: "hover:bg-amber-50", border: "group-hover:border-amber-200" },
  };

  const defaultStyle = { icon: FiCode, color: "text-gray-500", bg: "bg-gray-50", hoverBg: "hover:bg-gray-50", border: "group-hover:border-gray-200" };

  const programs = categories.length > 0
    ? categories
        .filter((cat) => cat.status)
        .map((category) => {
          const style = categoryStyleMap[category.title] || defaultStyle;
          return {
            id: category.id,
            title: category.title,
            description: category.description || "",
            icon: style.icon,
            color: style.color,
            bg: style.bg,
            hoverBg: style.hoverBg,
            border: style.border,
          };
        })
    : [];

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
          <div className="bg-white rounded-xl shadow-xl border border-gray-200/80 py-4 px-2 w-[520px]">
            {/* Header */}
            <div className="mb-2 px-3">
              <h3 className="text-sm font-semibold text-gray-900">
                Training Programmes
              </h3>
            </div>

            {/* Programs List */}
            <div className="max-h-[360px] overflow-y-auto">
              {isLoading ? (
                <div className="space-y-1 px-1">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="flex items-center space-x-3 px-3 py-2.5 animate-pulse">
                      <div className="w-8 h-8 bg-gray-100 rounded-lg shrink-0" />
                      <div className="flex-1 space-y-1.5">
                        <div className="h-3.5 bg-gray-100 rounded w-2/3" />
                        <div className="h-2.5 bg-gray-50 rounded w-full" />
                      </div>
                    </div>
                  ))}
                </div>
              ) : programs.length > 0 ? (
                <div className="space-y-0.5 px-1">
                  {programs.map((program) => {
                    const IconComponent = program.icon;
                    return (
                      <div
                        key={program.id}
                        onClick={() => {
                          router.push(
                            `/programmes?category=${encodeURIComponent(program.title)}`
                          );
                          onClose();
                        }}
                        className="group flex items-center space-x-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-150"
                      >
                        <div
                          className={`p-1.5 ${program.bg} rounded-lg shrink-0`}
                        >
                          <IconComponent
                            className={`w-4 h-4 ${program.color}`}
                          />
                        </div>
                        <div className="flex-1 min-w-0">
                          <h4 className="font-medium text-[13px] text-gray-900 truncate">
                            {program.title}
                          </h4>
                          {program.description && (
                            <p className="text-[11px] text-gray-400 truncate mt-0.5">
                              {program.description}
                            </p>
                          )}
                        </div>
                        <FiArrowRight className="w-3.5 h-3.5 text-gray-300 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all shrink-0" />
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="text-center py-6 text-gray-500">
                  <FiGrid className="w-6 h-6 mx-auto mb-1.5 text-gray-300" />
                  <p className="text-xs">No programmes available</p>
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="mt-2 pt-3 mx-3 border-t border-gray-100">
              <div className="flex items-center justify-between">
                <button
                  onClick={() => {
                    router.push("/course-match");
                    onClose();
                  }}
                  className="text-xs text-yellow-600 hover:text-yellow-700 font-medium flex items-center space-x-1 transition-colors"
                >
                  <span>Course Match Help</span>
                  <FiArrowRight className="w-3 h-3" />
                </button>
                <button
                  onClick={() => {
                    router.push("/programmes");
                    onClose();
                  }}
                  className="text-xs text-gray-500 hover:text-gray-900 font-medium flex items-center space-x-1 transition-colors"
                >
                  <span>See all programmes</span>
                  <FiArrowRight className="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default ProgramsMenu;
