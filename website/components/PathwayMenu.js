"use client";

import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import {
  FiUsers,
  FiRefreshCw,
  FiBookOpen,
  FiHeart,
  FiPlay,
  FiTrendingUp,
  FiArrowRight,
} from "react-icons/fi";
import { pathways } from "../data/pathways";

const PathwayMenu = ({ isOpen, onClose }) => {
  const router = useRouter();

  // Icon mapping
  const iconMap = {
    FiUsers,
    FiRefreshCw,
    FiBookOpen,
    FiHeart,
    FiPlay,
    FiTrendingUp
  };

  // Convert pathways object to array for menu display
  const pathwayItems = Object.values(pathways).map(pathway => ({
    id: pathway.id,
    title: pathway.title,
    icon: iconMap[pathway.icon]
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
                Pathways
              </h3>
              <p className="text-sm text-gray-600">
                Find your perfect learning path
              </p>
            </div>

            {/* Pathways Grid */}
            <div className="grid grid-cols-2 gap-4">
              {pathwayItems.map((pathway) => {
                const IconComponent = pathway.icon;
                return (
                  <div
                    key={pathway.id}
                    onClick={() => {
                      router.push(`/pathway/${pathway.id}`);
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
                            {pathway.title}
                          </h4>
                          <FiArrowRight className="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default PathwayMenu;
 