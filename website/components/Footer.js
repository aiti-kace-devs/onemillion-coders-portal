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
} from "react-icons/fi";
import { useMemo } from "react";

const contactIconMap = {
  "address-book": FiMapPin,
  address: FiMapPin,
  email: FiMail,
  phone: FiPhone,
};

const socialIconMap = {
  facebook: FiFacebook,
  twitter: FiTwitter,
  "x(twitter)": FiTwitter,
  linkedin: FiLinkedin,
  instagram: FiInstagram,
};

function getContactIcon(iconKey) {
  const key = (iconKey ?? "").toLowerCase().trim();
  return contactIconMap[key] ?? FiMapPin;
}

function getSocialIcon(iconKey) {
  const key = (iconKey ?? "").toLowerCase().trim();
  return socialIconMap[key] ?? null;
}

const Footer = ({ data }) => {
  const footerData = useMemo(() => {
    const raw = data?.data ?? data;
    if (!raw) {
      return null;
    }
    return {
      contactUs: raw.contact_us?.contact_us ?? [],
      quickLinks: raw.quick_links?.quick_links ?? [],
      copyrights: raw.copyrights ?? null,
      collaborators: raw.collaborators ?? null,
      socialMedia: raw.social_media?.social_media ?? [],
    };
  }, [data]);

  const hasData = footerData && (
    footerData.contactUs.length > 0 ||
    footerData.quickLinks.length > 0 ||
    footerData.copyrights ||
    footerData.collaborators ||
    footerData.socialMedia.length > 0
  );

  const contactItems = hasData ? footerData.contactUs : [
    { name: "Address", value: "MoCDTI Building, Accra, Ghana", icon: { value: "address-book" } },
    { name: "Email", value: "support@onemillioncoders.gov.gh", icon: { value: "email" } },
    { name: "Phone", value: "(+233) -302-666465", icon: { value: "phone" } },
  ];

  const quickLinkItems = hasData ? footerData.quickLinks : [
    { name: "Home", link: "/" },
    { name: "Programmes", link: "/programmes" },
    { name: "Pathway", link: "/pathway" },
    { name: "Community", link: "/community" },
    { name: "About", link: "/about" },
    { name: "Course Match", link: "/course-match" },
  ];

  const copyrightText = hasData && footerData.copyrights?.copyright
    ? footerData.copyrights.copyright
    : `© ${new Date().getFullYear()} One Million Coders Ghana. All rights reserved.`;

  const footerLinks = hasData && footerData.copyrights?.footer_links?.length
    ? footerData.copyrights.footer_links
    : [
        { name: "Terms of Service & Privacy Policy", url: "/terms-and-privacy" },
      ];

  const collaboratorsBlock = hasData ? footerData.collaborators : null;
  const description = collaboratorsBlock?.description ?? "Empowering Ghana's digital future through world-class technology education and training programs.";
  const collaborators = collaboratorsBlock?.collaborators ?? [];

  const socialItems = hasData && footerData.socialMedia.length > 0
    ? footerData.socialMedia
    : [
        { name: "Facebook", url: "#", icon: { value: "facebook" } },
        { name: "X(twitter)", url: "#", icon: { value: "twitter" } },
        { name: "LinkedIn", url: "#", icon: { value: "linkedin" } },
        { name: "Instagram", url: "#", icon: { value: "instagram" } },
      ];

  const poweredByLink = hasData && footerData.copyrights?.footer_links
    ? footerData.copyrights.footer_links.find((l) => /powered|gi-kace/i.test(l.name))
    : { name: "Powered by GI-KACE", url: "https://gi-kace.gov.gh/" };

  return (
    <footer className="bg-gray-900 text-white relative overflow-hidden">
      <GhanaGradientBar height="1px" position="top" />

      <div className="absolute inset-0 opacity-5">
        <div className="absolute top-20 right-20 w-64 h-64">
          <div
            className="w-full h-full"
            style={{
              background: `radial-gradient(circle, rgba(251, 191, 36, 0.3) 1px, transparent 1px)`,
              backgroundSize: "15px 15px",
            }}
          />
        </div>
        <div className="absolute bottom-20 left-20 w-12 h-12">
          <svg className="w-full h-full text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-red-600/5 via-yellow-400/5 to-green-600/5 rounded-full blur-3xl" />
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Brand / Collaborators */}
          <div className="lg:col-span-2">
            <div className="flex items-center space-x-4 mb-6">
              {collaborators.length > 0 ? (
                collaborators.slice(0, 2).map((c) => (
                  c.image?.url ? (
                    <Image
                      key={c.id}
                      src={c.image.url}
                      alt={c.name}
                      width={c.name === "One Million Coders" ? 150 : 50}
                      height={c.name === "One Million Coders" ? 48 : 50}
                      className={c.name === "One Million Coders" ? "h-12 w-auto" : "rounded-lg w-12 h-12"}
                    />
                  ) : null
                ))
              ) : (
                <>
                  <Image src="/images/white-logo.png" alt="One Million Coders" width={150} height={48} className="h-12 w-auto" />
                  <Image src="/images/moc-logo.png" alt="MOC Logo" width={50} height={50} className="rounded-lg w-12 h-12" />
                </>
              )}
            </div>
            <p className="text-gray-300 text-lg leading-relaxed mb-6 max-w-md">
              {description}
            </p>
            <div className="flex space-x-4">
              {socialItems.map((item) => {
                const Icon = getSocialIcon(item.icon?.value ?? item.icon?.key);
                if (!Icon) return null;
                return (
                  <a
                    key={item.id ?? item.name}
                    href={item.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-yellow-500 hover:text-gray-900 transition-all duration-200"
                    aria-label={item.name}
                  >
                    <Icon size={18} />
                  </a>
                );
              })}
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-lg font-semibold mb-4 text-yellow-400">Quick Links</h3>
            <ul className="space-y-3">
              {quickLinkItems.map((link) => (
                <li key={link.id ?? link.link}>
                  <Link
                    href={link.link}
                    className="text-gray-300 hover:text-yellow-400 transition-colors"
                  >
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="text-lg font-semibold mb-4 text-yellow-400">Contact</h3>
            <ul className="space-y-3">
              {contactItems.map((item) => {
                const Icon = getContactIcon(item.icon?.value ?? item.icon?.key);
                return (
                  <li key={item.id ?? item.name} className="flex items-center space-x-3">
                    <Icon className="text-yellow-400 flex-shrink-0" size={16} />
                    {item.name === "Email" && item.value ? (
                      <a href={`mailto:${item.value}`} className="text-gray-300 text-sm hover:text-yellow-400 transition-colors">
                        {item.value}
                      </a>
                    ) : item.name === "Phone" && item.value ? (
                      <a href={`tel:${item.value.replace(/\s/g, "")}`} className="text-gray-300 text-sm hover:text-yellow-400 transition-colors">
                        {item.value}
                      </a>
                    ) : (
                      <span className="text-gray-300 text-sm">{item.value}</span>
                    )}
                  </li>
                );
              })}
            </ul>
          </div>
        </div>

        {/* Bottom Section */}
        <div className="border-t border-gray-700 mt-12 pt-8">
          <div className="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <div className="flex items-center space-x-4">
              <p className="text-gray-400 text-sm text-center">{copyrightText}</p>
            </div>
            <div className="flex space-x-6 text-sm">
              {footerLinks.map((link) => (
                <Link
                  key={link.id ?? link.url}
                  href={link.url}
                  className="text-gray-400 hover:text-yellow-400 transition-colors"
                  {...(link.url?.startsWith("http") ? { target: "_blank", rel: "noopener noreferrer" } : {})}
                >
                  {link.name}
                </Link>
              ))}
            </div>
          </div>
        </div>

        {/* Powered by */}
        {poweredByLink && (
          <div className="mt-6 pb-2">
            <div className="text-center">
              <p className="text-gray-500 text-sm">
                <a
                  href={poweredByLink.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-gray-400 hover:text-yellow-400 transition-colors duration-200 underline decoration-dotted underline-offset-2"
                >
                  {poweredByLink.name}
                </a>
              </p>
            </div>
          </div>
        )}
      </div>

      <div className="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-red-600 via-yellow-400 to-green-600" />
    </footer>
  );
};

export default Footer;
