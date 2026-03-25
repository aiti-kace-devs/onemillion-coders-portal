"use client";

import { useState, useMemo, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import { FiX, FiChevronLeft, FiChevronRight, FiImage } from "react-icons/fi";

const BLUR_DATA_URL = "data:image/svg+xml;base64," + btoa(
  '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="100%" height="100%" fill="#f3f4f6"/></svg>'
);

// Derive grid span from actual image aspect ratio + position for visual variety
function getGridSpan(ratio, index) {
  // Every 5th image gets promoted to a featured size for layout variety
  if (index % 5 === 0) return { col: 2, row: 2 };    // featured → 2x2
  if (index % 7 === 0) return { col: 2, row: 1 };    // wide feature → 2x1
  if (index % 6 === 0) return { col: 1, row: 2 };    // tall feature → 1x2

  // Rest follow their natural aspect ratio
  if (ratio >= 1.8) return { col: 2, row: 1 };       // very wide → 2 cols, 1 row
  if (ratio >= 0.5 && ratio < 0.8) return { col: 1, row: 2 }; // portrait → 1 col, 2 rows
  if (ratio < 0.5) return { col: 1, row: 2 };        // very tall → 1 col, 2 rows
  return { col: 1, row: 1 };                          // landscape/square → 1x1
}

export default function GalleryClient({ data }) {
  const [selectedImage, setSelectedImage] = useState(null);
  const [imageErrors, setImageErrors] = useState({});
  const [imageLoaded, setImageLoaded] = useState({});
  const [imageDimensions, setImageDimensions] = useState({});
  const [lightboxReady, setLightboxReady] = useState(false);

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
              title: item.title || item.name || "",
              width: imageData.width || null,
              height: imageData.height || null,
            });
          }
        });
      }
    });
    return images;
  }, [data]);

  // Probe natural dimensions for images that don't have them from the API
  useEffect(() => {
    galleryImages.forEach((image) => {
      if (image.width && image.height) {
        setImageDimensions((prev) => ({
          ...prev,
          [image.id]: { width: image.width, height: image.height },
        }));
        return;
      }
      const img = new window.Image();
      img.onload = () => {
        setImageDimensions((prev) => ({
          ...prev,
          [image.id]: { width: img.naturalWidth, height: img.naturalHeight },
        }));
      };
      img.onerror = () => {
        setImageDimensions((prev) => ({
          ...prev,
          [image.id]: { width: 4, height: 3 },
        }));
      };
      img.src = image.src;
    });
  }, [galleryImages]);

  const openLightbox = useCallback((image) => {
    setLightboxReady(false);
    setSelectedImage(image);
    document.body.style.overflow = 'hidden';
  }, []);

  const closeLightbox = useCallback(() => {
    setSelectedImage(null);
    setLightboxReady(false);
    document.body.style.overflow = 'unset';
  }, []);

  const nextImage = useCallback(() => {
    setLightboxReady(false);
    setSelectedImage((current) => {
      const currentIndex = galleryImages.findIndex(img => img.id === current.id);
      return galleryImages[(currentIndex + 1) % galleryImages.length];
    });
  }, [galleryImages]);

  const prevImage = useCallback(() => {
    setLightboxReady(false);
    setSelectedImage((current) => {
      const currentIndex = galleryImages.findIndex(img => img.id === current.id);
      return galleryImages[(currentIndex - 1 + galleryImages.length) % galleryImages.length];
    });
  }, [galleryImages]);

  // Keyboard navigation for lightbox
  useEffect(() => {
    if (!selectedImage) return;

    const handleKeyDown = (e) => {
      switch (e.key) {
        case "Escape":
          closeLightbox();
          break;
        case "ArrowRight":
          nextImage();
          break;
        case "ArrowLeft":
          prevImage();
          break;
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [selectedImage, closeLightbox, nextImage, prevImage]);

  // Swipe gesture state
  const [touchStart, setTouchStart] = useState(null);
  const handleTouchStart = (e) => setTouchStart(e.touches[0].clientX);
  const handleTouchEnd = (e) => {
    if (touchStart === null) return;
    const diff = touchStart - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) {
      diff > 0 ? nextImage() : prevImage();
    }
    setTouchStart(null);
  };

  const selectedIndex = selectedImage
    ? galleryImages.findIndex(img => img.id === selectedImage.id)
    : -1;

  if (!galleryImages || galleryImages.length === 0) {
    return (
      <div className="min-h-screen bg-white flex flex-col items-center justify-center">
        <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
          <FiImage className="w-8 h-8 text-gray-300" />
        </div>
        <p className="mt-4 text-gray-400 text-lg font-light">No images available</p>
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
          <div
            className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5 auto-rows-[200px]"
            style={{ gridAutoFlow: 'dense' }}
          >
            {galleryImages.map((image, index) => {
              const dims = imageDimensions[image.id];
              const ratio = dims ? dims.width / dims.height : 1.5;
              const span = getGridSpan(ratio, index);

              const colClass = span.col === 2 ? 'md:col-span-2' : 'col-span-1';
              const rowClass = span.row === 2 ? 'md:row-span-2' : 'row-span-1';

              return (
                <motion.div
                  key={image.id}
                  initial={{ opacity: 0, y: 30 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, margin: "-50px" }}
                  transition={{ duration: 0.6, delay: index % 10 * 0.05 }}
                  className={`group cursor-pointer ${colClass} ${rowClass}`}
                  onClick={() => openLightbox(image)}
                >
                  <div className="relative w-full h-full overflow-hidden rounded-xl md:rounded-2xl bg-gray-50 shadow-sm hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-1">
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
                        sizes="(max-width: 640px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, 25vw"
                        placeholder="blur"
                        blurDataURL={BLUR_DATA_URL}
                        onLoad={() => setImageLoaded(prev => ({ ...prev, [image.id]: true }))}
                        onError={() => setImageErrors(prev => ({ ...prev, [image.id]: true }))}
                      />
                    )}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    {image.title && (
                      <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-3 md:p-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <p className="text-white text-sm font-medium truncate">{image.title}</p>
                      </div>
                    )}
                  </div>
                </motion.div>
              );
            })}
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
            onTouchStart={handleTouchStart}
            onTouchEnd={handleTouchEnd}
          >
            {/* Close button */}
            <button
              onClick={closeLightbox}
              className="absolute top-3 right-3 md:top-6 md:right-6 w-11 h-11 md:w-12 md:h-12 bg-black/60 md:bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-20 border border-white/20"
            >
              <FiX className="w-5 h-5 md:w-6 md:h-6" />
            </button>

            {/* Image counter */}
            <div className="absolute top-3 left-3 md:top-6 md:left-6 px-3 py-1.5 bg-black/60 md:bg-white/10 backdrop-blur-md rounded-full text-white text-sm font-medium z-20 border border-white/20">
              {selectedIndex + 1} / {galleryImages.length}
            </div>

            {/* Desktop: side arrows */}
            {galleryImages.length > 1 && (
              <>
                <button
                  onClick={(e) => { e.stopPropagation(); prevImage(); }}
                  className="hidden md:flex absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronLeft className="w-6 h-6" />
                </button>
                <button
                  onClick={(e) => { e.stopPropagation(); nextImage(); }}
                  className="hidden md:flex absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full items-center justify-center text-white hover:bg-white/20 transition-all duration-200 z-10 border border-white/10"
                >
                  <FiChevronRight className="w-6 h-6" />
                </button>
              </>
            )}

            {/* Mobile: bottom navigation bar */}
            {galleryImages.length > 1 && (
              <div className="md:hidden absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-6 z-20" onClick={(e) => e.stopPropagation()}>
                <button
                  onClick={prevImage}
                  className="w-12 h-12 bg-black/60 backdrop-blur-md rounded-full flex items-center justify-center text-white active:bg-white/30 transition-all duration-200 border border-white/20"
                >
                  <FiChevronLeft className="w-6 h-6" />
                </button>
                <button
                  onClick={nextImage}
                  className="w-12 h-12 bg-black/60 backdrop-blur-md rounded-full flex items-center justify-center text-white active:bg-white/30 transition-all duration-200 border border-white/20"
                >
                  <FiChevronRight className="w-6 h-6" />
                </button>
              </div>
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
                {/* Loading spinner */}
                {!lightboxReady && !imageErrors[selectedImage.id] && (
                  <div className="absolute inset-0 flex items-center justify-center z-20">
                    <div className="w-10 h-10 border-2 border-white/20 border-t-white/80 rounded-full animate-spin" />
                  </div>
                )}
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
                    key={selectedImage.id}
                    src={selectedImage.src}
                    alt={selectedImage.title}
                    fill
                    className={`object-contain transition-opacity duration-300 ${lightboxReady ? 'opacity-100' : 'opacity-0'}`}
                    sizes="(max-width: 768px) 100vw, (max-width: 1200px) 90vw, 1200px"
                    placeholder="blur"
                    blurDataURL={BLUR_DATA_URL}
                    onLoad={() => setLightboxReady(true)}
                    onError={() => setImageErrors(prev => ({ ...prev, [selectedImage.id]: true }))}
                  />
                )}
              </div>
              {selectedImage.title && (
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4 pb-20 md:pb-6 md:p-6 rounded-b-lg">
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
