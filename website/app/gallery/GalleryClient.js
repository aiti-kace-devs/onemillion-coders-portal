"use client";

import { useState, useMemo } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import { FiX, FiChevronLeft, FiChevronRight } from "react-icons/fi";

export default function GalleryClient({ data }) {
  const [selectedImage, setSelectedImage] = useState(null);
  const [imageErrors, setImageErrors] = useState({});
  const [imageLoaded, setImageLoaded] = useState({});

  // Helper function to get random aspect ratios for masonry effect
  const getRandomAspectRatio = (index) => {
    const ratios = [
      'aspect-square',      // 1:1
      'aspect-[4/5]',       // 4:5 (portrait)
      'aspect-[3/4]',       // 3:4 (portrait)
      'aspect-[5/4]',       // 5:4 (landscape)
      'aspect-[4/3]',       // 4:3 (landscape)
      'aspect-[3/2]',       // 3:2 (landscape)
      'aspect-[2/3]',       // 2:3 (tall portrait)
    ];
    return ratios[index % ratios.length];
  };

  const galleryImages = useMemo(() => {
    if (!data || !data.sections) return [];

    const images = [];
    data.sections.forEach((section) => {
      if (section.section_items) {
        section.section_items.forEach((item) => {
          if (item.media || item.image) {
            const imageData = item.media || item.image;
            images.push({
              id: item.id || `${section.id}-${Math.random()}`,
              src: imageData.url,
              title: item.title || item.name || ""
            });
          }
        });
      }
    });
    return images;
  }, [data]);

  const openLightbox = (image) => {
    setSelectedImage(image);
    document.body.style.overflow = 'hidden';
  };

  const closeLightbox = () => {
    setSelectedImage(null);
    document.body.style.overflow = 'unset';
  };

  const nextImage = () => {
    const currentIndex = galleryImages.findIndex(img => img.id === selectedImage.id);
    const nextIndex = (currentIndex + 1) % galleryImages.length;
    setSelectedImage(galleryImages[nextIndex]);
  };

  const prevImage = () => {
    const currentIndex = galleryImages.findIndex(img => img.id === selectedImage.id);
    const prevIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
    setSelectedImage(galleryImages[prevIndex]);
  };

  if (!galleryImages || galleryImages.length === 0) {
    return (
      <div className="min-h-screen bg-white flex flex-col items-center justify-center">
        <div className="relative">
          <div className="w-20 h-20 bg-gradient-to-br from-amber-400 via-red-500 to-green-600 rounded-2xl opacity-20 animate-pulse"></div>
          <div className="absolute inset-0 w-20 h-20 bg-gradient-to-br from-amber-400 via-red-500 to-green-600 rounded-2xl opacity-10 animate-ping"></div>
        </div>
        <p className="mt-6 text-gray-500 text-lg font-light">Loading gallery...</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      {/* Title */}
      <div className="pt-20 pb-12 text-center">
        <h1 className="text-4xl font-light text-gray-800 mb-3">Gallery</h1>
        <div className="w-24 h-px bg-gradient-to-r from-amber-400 via-red-500 to-green-600 mx-auto"></div>
      </div>

      {/* Gallery Grid */}
      <div className="pb-20 px-4 md:px-6">
        <div className="max-w-7xl mx-auto">
          <div className="columns-1 sm:columns-2 md:columns-3 lg:columns-4 xl:columns-5 gap-4 md:gap-6">
            {galleryImages.map((image, index) => (
              <motion.div
                key={image.id}
                initial={{ opacity: 0, y: 30 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.03 }}
                className="break-inside-avoid mb-4 md:mb-6 group cursor-pointer"
                onClick={() => openLightbox(image)}
              >
                <div className="relative overflow-hidden rounded-xl md:rounded-2xl bg-gray-50 shadow-sm hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-1">
                  <div className={`relative ${getRandomAspectRatio(index)}`}>
                    {/* Logo placeholder shown while loading or on error */}
                    {(!imageLoaded[image.id] || imageErrors[image.id]) && (
                      <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center z-10">
                        <Image
                          src="/images/one-million-coders-logo.png"
                          alt="One Million Coders"
                          width={120}
                          height={40}
                          className="opacity-15"
                        />
                      </div>
                    )}
                    {!imageErrors[image.id] && (
                      <Image
                        src={image.src}
                        alt={image.title}
                        fill
                        className="object-cover transition-all duration-700 group-hover:scale-105 group-hover:brightness-110"
                        sizes="(max-width: 640px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, (max-width: 1280px) 25vw, 20vw"
                        onLoad={() => setImageLoaded(prev => ({ ...prev, [image.id]: true }))}
                        onError={() => setImageErrors(prev => ({ ...prev, [image.id]: true }))}
                      />
                    )}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                  </div>
                  {image.title && (
                    <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-3 md:p-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                      <p className="text-white text-sm font-medium truncate">{image.title}</p>
                    </div>
                  )}
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </div>

      {/* Lightbox */}
      <AnimatePresence>
        {selectedImage && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={closeLightbox}
          >
            <button
              onClick={closeLightbox}
              className="absolute top-4 right-4 md:top-6 md:right-6 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
            >
              <FiX className="w-5 h-5 md:w-6 md:h-6" />
            </button>

            {galleryImages.length > 1 && (
              <>
                <button
                  onClick={(e) => { e.stopPropagation(); prevImage(); }}
                  className="absolute left-4 md:left-6 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronLeft className="w-5 h-5 md:w-6 md:h-6" />
                </button>
                <button
                  onClick={(e) => { e.stopPropagation(); nextImage(); }}
                  className="absolute right-4 md:right-6 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronRight className="w-5 h-5 md:w-6 md:h-6" />
                </button>
              </>
            )}

            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              transition={{ duration: 0.3, ease: "easeOut" }}
              className="relative w-full h-full max-w-6xl max-h-[90vh]"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="relative w-full h-full rounded-lg overflow-hidden shadow-2xl">
                {imageErrors[selectedImage.id] ? (
                  <div className="absolute inset-0 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">
                    <Image
                      src="/images/one-million-coders-logo.png"
                      alt="One Million Coders"
                      width={180}
                      height={60}
                      className="opacity-15"
                    />
                  </div>
                ) : (
                  <Image
                    src={selectedImage.src}
                    alt={selectedImage.title}
                    fill
                    className="object-contain"
                    sizes="(max-width: 768px) 100vw, (max-width: 1200px) 90vw, 1200px"
                    onError={() => setImageErrors(prev => ({ ...prev, [selectedImage.id]: true }))}
                  />
                )}
              </div>
              {selectedImage.title && (
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4 md:p-6 rounded-b-lg">
                  <h3 className="text-white text-lg md:text-xl font-medium">{selectedImage.title}</h3>
                </div>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="h-1 bg-gradient-to-r from-amber-400 via-red-500 to-green-600"></div>
    </div>
  );
} 