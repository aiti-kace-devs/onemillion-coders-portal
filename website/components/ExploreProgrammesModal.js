"use client";

import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { motion, AnimatePresence } from "framer-motion";
import { FiX, FiArrowRight } from "react-icons/fi";

// Staggered entrance for the content blocks — feels a beat more considered
// than everything appearing at once.
const stagger = {
  show: { transition: { staggerChildren: 0.06, delayChildren: 0.08 } },
};
const rise = {
  hidden: { opacity: 0, y: 8 },
  show: { opacity: 1, y: 0, transition: { duration: 0.28, ease: "easeOut" } },
};

export default function ExploreProgrammesModal({ isOpen, onClose, onYes, onNo }) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!isOpen) return;
    const onKey = (e) => {
      if (e.key === "Escape") onClose?.();
    };
    document.addEventListener("keydown", onKey);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prevOverflow;
    };
  }, [isOpen, onClose]);

  if (!mounted) return null;

  return createPortal(
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          className="fixed inset-0 z-[100] bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
          onClick={onClose}
          role="dialog"
          aria-modal="true"
          aria-labelledby="explore-programmes-title"
        >
          <motion.div
            initial={{ opacity: 0, scale: 0.97, y: 12 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.97, y: 12 }}
            transition={{ duration: 0.22, ease: "easeOut" }}
            onClick={(e) => e.stopPropagation()}
            className="relative w-full max-w-lg bg-white rounded-[28px] shadow-[0_24px_70px_-20px_rgba(0,0,0,0.4)] overflow-hidden my-auto"
          >
            {/* Ghana accent */}
            <div className="h-[3px] bg-gradient-to-r from-red-500 via-yellow-400 to-green-500" />

            {/* Soft corner glow */}
            <div
              aria-hidden="true"
              className="pointer-events-none absolute -top-24 -right-24 w-64 h-64 rounded-full bg-yellow-200/40 blur-3xl"
            />

            <button
              type="button"
              onClick={onClose}
              aria-label="Close"
              className="group cursor-pointer absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-900 hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
            >
              <FiX className="w-4 h-4 transition-transform duration-200 group-hover:rotate-90" />
            </button>

            <motion.div
              variants={stagger}
              initial="hidden"
              animate="show"
              className="relative px-7 sm:px-10 pt-10 sm:pt-12 pb-7 sm:pb-9"
            >
              <motion.div variants={rise} className="flex items-center gap-2 mb-4">
                <span className="w-4 h-[2px] bg-yellow-400 rounded-full" />
                <p className="text-[11px] tracking-[0.2em] uppercase text-gray-500 font-semibold">
                  Before you register
                </p>
              </motion.div>

              <motion.h2
                variants={rise}
                id="explore-programmes-title"
                className="text-[26px] sm:text-[30px] font-bold text-gray-900 tracking-tight leading-[1.15]"
              >
                Have you explored our programmes?
              </motion.h2>

              <motion.p
                variants={rise}
                className="text-[15px] text-gray-500 mt-4 leading-relaxed max-w-md"
              >
                Taking a few minutes to see what we offer helps you pick a
                programme that fits you, and find a centre nearby.
              </motion.p>

              <motion.div variants={rise} className="mt-8 space-y-2.5">
                <button
                  type="button"
                  onClick={onNo}
                  autoFocus
                  className="group cursor-pointer w-full inline-flex items-center justify-between px-5 py-[14px] rounded-2xl bg-gray-900 hover:bg-black text-white text-[15px] font-semibold shadow-[0_4px_14px_-4px_rgba(0,0,0,0.35)] hover:shadow-[0_8px_22px_-6px_rgba(0,0,0,0.4)] transition-all duration-200 hover:-translate-y-[1px] active:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2"
                >
                  <span>No, show me programmes</span>
                  <span className="flex items-center justify-center w-7 h-7 rounded-full bg-yellow-400 text-gray-900 transition-transform duration-200 group-hover:translate-x-1">
                    <FiArrowRight className="w-3.5 h-3.5" strokeWidth={2.5} />
                  </span>
                </button>

                <button
                  type="button"
                  onClick={onYes}
                  className="cursor-pointer w-full inline-flex items-center justify-center px-5 py-[13px] rounded-2xl bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2"
                >
                  Yes, continue to register
                </button>
              </motion.div>

              <motion.div
                variants={rise}
                className="mt-5 flex items-center justify-center gap-1.5 text-[11px] text-gray-400"
              >
                <span>Press</span>
                <kbd className="px-1.5 py-0.5 rounded-md bg-gray-100 border border-gray-200 text-gray-600 font-mono text-[10px] leading-none">
                  Esc
                </kbd>
                <span>to close</span>
              </motion.div>
            </motion.div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>,
    document.body
  );
}
