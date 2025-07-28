"use client";

import Image from "next/image";
import Link from "next/link";
import Button from "./Button";
import ProgramsMenu from "./ProgramsMenu";
import PathwayMenu from "./PathwayMenu";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import { FiArrowRight, FiMenu, FiX, FiChevronDown } from "react-icons/fi";
import { useState, useEffect, useRef } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, usePathname } from "next/navigation";
import { getCategoriesData } from "../services";

export default function Header() {
  const router = useRouter();
  const pathname = usePathname();
  const [isProgramsMenuOpen, setIsProgramsMenuOpen] = useState(false);
  const [isPathwayMenuOpen, setIsPathwayMenuOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [categories, setCategories] = useState([]);

  const programsRef = useRef(null);
  const pathwayRef = useRef(null);

  // Fetch categories for the programs menu
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const categoriesData = await getCategoriesData();
        setCategories(categoriesData || []);
      } catch (error) {
        console.error("Failed to fetch categories for header:", error);
      }
    };

    fetchCategories();
  }, []);

  const handleProgramsClick = (e) => {
    e.preventDefault();
    setIsProgramsMenuOpen(!isProgramsMenuOpen);
    setIsPathwayMenuOpen(false); // Close other menus
  };

  const handlePathwayClick = (e) => {
    e.preventDefault();
    setIsPathwayMenuOpen(!isPathwayMenuOpen);
    setIsProgramsMenuOpen(false); // Close other menus
  };

  const closeProgramsMenu = () => {
    setIsProgramsMenuOpen(false);
  };

  const closePathwayMenu = () => {
    setIsPathwayMenuOpen(false);
  };

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
  };

  // Helper function to check if a path is active
  const isActiveLink = (path) => {
    if (path === "/programmes") {
      return pathname === "/programmes" || pathname.startsWith("/programmes/");
    }
    return pathname === path;
  };

  // Close menus when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (programsRef.current && !programsRef.current.contains(event.target)) {
        setIsProgramsMenuOpen(false);
      }
      if (pathwayRef.current && !pathwayRef.current.contains(event.target)) {
        setIsPathwayMenuOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  return (
    <header className="bg-white sticky top-0 z-50 relative">
      {/* Ghana Flag Ribbon at Top */}
      <GhanaGradientBar height="4px" position="top" zIndex={50} />

      {/* Subtle Ghana Star Pattern */}
      {/* <div className="absolute inset-0 opacity-2">
        <div className="absolute top-4 right-20 w-4 h-4">
          <svg
            className="w-full h-full text-black"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
      </div> */}

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        {/* Main Header */}
        <div className="flex justify-between items-center py-4">
          {/* Logo Section - Left side */}
          <div className="flex items-center space-x-3 sm:space-x-4">
            <Link
              href="/"
              className="flex items-center space-x-3 sm:space-x-4 group"
            >
              {/* One Million Coders Logo - Always visible */}
              <div className="block">
                <Image
                  src="/images/one-million-coders-logo.png"
                  alt="One Million Coders"
                  width={120}
                  height={38}
                  className="h-10 sm:h-12 w-auto"
                />
              </div>
            </Link>
          </div>

          {/* Navigation - Desktop only */}
          <nav className="hidden lg:flex items-center space-x-6 relative">
            <Link
              href="/"
              className={`nav-pill ${isActiveLink("/") ? "active" : ""}`}
            >
              Home
            </Link>
            <Link
              href="/about"
              className={`nav-pill ${isActiveLink("/about") ? "active" : ""}`}
            >
              About
            </Link>
            <div ref={programsRef} className="relative">
              <button
                onClick={handleProgramsClick}
                className={`nav-pill flex items-center space-x-1 ${
                  isActiveLink("/programmes") ? "active" : ""
                }`}
              >
                <span>Courses </span>
                <FiChevronDown
                  className={`w-3 h-3 transition-transform duration-200 ${
                    isProgramsMenuOpen ? "rotate-180" : ""
                  }`}
                />
              </button>
              <ProgramsMenu
                isOpen={isProgramsMenuOpen}
                onClose={closeProgramsMenu}
                categories={categories}
              />
            </div>
            {/* <div ref={pathwayRef} className="relative">
              <button
                onClick={handlePathwayClick}
                className={`nav-pill flex items-center space-x-1 ${
                  isActiveLink("/pathway") ? "active" : ""
                }`}
              >
                <span>Pathway</span>
                <FiChevronDown
                  className={`w-3 h-3 transition-transform duration-200 ${
                    isPathwayMenuOpen ? "rotate-180" : ""
                  }`}
                />
              </button>
              <PathwayMenu
                isOpen={isPathwayMenuOpen}
                onClose={closePathwayMenu}
              />
            </div> */}
            <Link
              href="/pathway"
              className={`nav-pill ${isActiveLink("/pathway") ? "active" : ""}`}
            >
              Pathways
            </Link>
            <Link
              href="/gallery"
              className={`nav-pill ${isActiveLink("/gallery") ? "active" : ""}`}
            >
              Gallery
            </Link>
            <Link
              href="/community"
              className={`nav-pill ${
                isActiveLink("/community") ? "active" : ""
              }`}
            >
              Testimonials
            </Link>
            <Link
              href="/faqs"
              className={`nav-pill ${isActiveLink("/faqs") ? "active" : ""}`}
            >
              FAQs
            </Link>
          </nav>

          {/* Right Section */}
          <div className="flex items-center space-x-3 sm:space-x-4">
            {/* CTA Button - Hidden on small mobile, visible sm+ */}
            <div className="hidden sm:block">
              <Button
                onClick={() => router.push("/register")}
                icon={FiArrowRight}
                variant="primary"
                size="medium"
                iconPosition="right"
              >
                Register
              </Button>
            </div>

            {/* Vertical Bar Separator - Desktop only */}
            <div className="hidden lg:block h-14 w-px bg-gray-300"></div>

            {/* MOC Logo - Always visible */}
            <div className="block">
              <Image
                src="/images/moc-logo.png"
                alt="MOC Logo"
                width={50}
                height={50}
                className="rounded-lg w-10 h-10 sm:w-14 sm:h-14 md:w-14 md:h-14"
              />
            </div>

            {/* Mobile Menu Button */}
            <button
              onClick={toggleMobileMenu}
              className="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
            >
              {isMobileMenuOpen ? (
                <FiX className="w-5 h-5" />
              ) : (
                <FiMenu className="w-5 h-5" />
              )}
            </button>
          </div>
        </div>

        {/* Mobile Navigation Menu */}
        <AnimatePresence>
          {isMobileMenuOpen && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              transition={{ duration: 0.3, ease: "easeInOut" }}
              className="lg:hidden border-t border-gray-100 overflow-hidden"
            >
              <div className="py-4 space-y-2">
                {/* Mobile Navigation Links */}
                <Link
                  href="/"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Home
                </Link>
                <Link
                  href="/programmes"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/programmes")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Programmes
                </Link>
                <Link
                  href="/pathway"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/pathway")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Pathways
                </Link>
                <Link
                  href="/community"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/community")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Community
                </Link>
                <Link
                  href="/about"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/about")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  About
                </Link>
                <Link
                  href="/faqs"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/faqs")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  FAQs
                </Link>
                <Link
                  href="/gallery"
                  className={`block px-4 py-3 rounded-lg transition-colors font-medium ${
                    isActiveLink("/gallery")
                      ? "text-yellow-600 bg-yellow-50 font-semibold"
                      : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                  }`}
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  Gallery
                </Link>

                {/* Mobile CTA Button */}
                <div className="pt-4 border-t border-gray-100 mt-4">
                  <Button
                    onClick={() => router.push("/register")}
                    icon={FiArrowRight}
                    variant="primary"
                    size="large"
                    iconPosition="right"
                    className="w-full justify-center"
                  >
                    Register
                  </Button>
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </header>
  );
}
