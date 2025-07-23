"use client";

import Image from "next/image";
import Link from "next/link";
import { GhanaGradientBar } from "@/components/GhanaGradients";
import {
  FiMail,
  FiPhone,
  FiMapPin,
  FiTwitter,
  FiFacebook,
  FiLinkedin,
  FiInstagram,
  FiExternalLink,
} from "react-icons/fi";

const Footer = () => {
  return (
    <footer className="bg-gray-900 text-white relative overflow-hidden">
      {/* Ghana Flag Top Border */}
      <GhanaGradientBar height="1px" position="top" />

      {/* Ghana Pattern Background */}
      <div className="absolute inset-0 opacity-5">
        {/* Map Pattern */}
        <div className="absolute top-20 right-20 w-64 h-64">
          <div
            className="w-full h-full"
            style={{
              background: `radial-gradient(circle, rgba(251, 191, 36, 0.3) 1px, transparent 1px)`,
              backgroundSize: "15px 15px",
            }}
          ></div>
        </div>

        {/* Ghana Stars */}
        <div className="absolute bottom-20 left-20 w-12 h-12">
          <svg
            className="w-full h-full text-yellow-500"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>

        {/* Flag Colors Ambient */}
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-red-600/5 via-yellow-400/5 to-green-600/5 rounded-full blur-3xl"></div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Brand Section */}
          <div className="lg:col-span-2">
            <div className="flex items-center space-x-4 mb-6">
              {/* One Million Coders Logo */}
              <Image
                src="/images/white-logo.png"
                alt="One Million Coders"
                width={150}
                height={48}
                className="h-12 w-auto"
              />
              {/* MOC Logo */}
              <Image
                src="/images/moc-logo.png"
                alt="MOC Logo"
                width={50}
                height={50}
                className="rounded-lg w-12 h-12"
              />
            </div>
            <p className="text-gray-300 text-lg leading-relaxed mb-6 max-w-md">
              Empowering Ghana&apos;s digital future through world-class
              technology education and training programs.
            </p>
            <div className="flex space-x-4">
              <a
                href="#"
                className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200"
              >
                <FiFacebook size={18} />
              </a>
              <a
                href="#"
                className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200"
              >
                <FiTwitter size={18} />
              </a>
              <a
                href="#"
                className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200"
              >
                <FiLinkedin size={18} />
              </a>
              <a
                href="#"
                className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200"
              >
                <FiInstagram size={18} />
              </a>
            </div>
          </div>

          {/* Quick Links - Matching Header Navigation */}
          <div>
            <h3 className="text-lg font-semibold mb-4 text-yellow-400">
              Quick Links
            </h3>
            <ul className="space-y-3">
              <li>
                <Link
                  href="/"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  Home
                </Link>
              </li>
              <li>
                <Link
                  href="/programmes"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  Programmes
                </Link>
              </li>
              <li>
                <Link
                  href="/pathway"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  Pathway
                </Link>
              </li>
              <li>
                <Link
                  href="/community"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  Community
                </Link>
              </li>
              <li>
                <Link
                  href="/about"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  About
                </Link>
              </li>
              <li>
                <Link
                  href="/course-match"
                  className="text-gray-300 hover:text-yellow-400 transition-colors"
                >
                  Course Match
                </Link>
              </li>
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h3 className="text-lg font-semibold mb-4 text-yellow-400">
              Contact
            </h3>
            <ul className="space-y-3">
              <li className="flex items-center space-x-3">
                <FiMapPin className="text-yellow-400 flex-shrink-0" size={16} />
                <span className="text-gray-300 text-sm">
                  MoCDTI Building, Accra, Ghana
                </span>
              </li>
              <li className="flex items-center space-x-3">
                <FiMail className="text-yellow-400 flex-shrink-0" size={16} />
                <span className="text-gray-300 text-sm">info@moc.gov.gh</span>
              </li>
              <li className="flex items-center space-x-3">
                <FiPhone className="text-yellow-400 flex-shrink-0" size={16} />
                <span className="text-gray-300 text-sm">
                  (+233) -302-666465
                </span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Section */}
        <div className="border-t border-gray-700 mt-12 pt-8">
          <div className="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <div className="flex items-center space-x-4">
              <p className="text-gray-400 text-sm text-center">
                © {new Date().getFullYear()} One Million Coders Ghana. All
                rights reserved.
              </p>
              <div className="hidden md:flex items-center space-x-2 text-sm text-gray-500">
                <span>🇬🇭</span>
                <span>Proudly Ghanaian</span>
              </div>
            </div>
            <div className="flex space-x-6 text-sm">
              <Link
                href="/privacy"
                className="text-gray-400 hover:text-yellow-400 transition-colors"
              >
                Privacy Policy
              </Link>
              <Link
                href="/terms"
                className="text-gray-400 hover:text-yellow-400 transition-colors"
              >
                Terms of Service
              </Link>
            </div>
          </div>
        </div>

        {/* Powered by GI-KACE Section - Subtle & Last */}
        <div className="mt-6 pb-2">
          <div className="text-center">
            <p className="text-gray-500 text-sm">
              Powered by{" "}
              <a
                href="https://gi-kace.gov.gh/"
                target="_blank"
                rel="noopener noreferrer"
                className="text-gray-400 hover:text-yellow-400 transition-colors duration-200 underline decoration-dotted underline-offset-2"
              >
                GI-KACE
              </a>
            </p>
          </div>
        </div>
      </div>

      {/* Ghana Flag Bottom Border */}
      <div className="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600"></div>
    </footer>
  );
};

export default Footer;
