import { getGalleryData } from "../../services";
import GalleryClient from "./GalleryClient";

export const revalidate = 60;

export default async function GalleryPage() {
  let galleryData = null;

  try {
    galleryData = await getGalleryData();
  } catch (error) {
    console.error('Failed to fetch gallery data:', error);
  }

  if (!galleryData) {
    return (
      <div className="min-h-screen bg-white flex flex-col items-center justify-center">
        <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
          <svg className="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
          </svg>
        </div>
        <p className="text-gray-400 text-lg font-light">Gallery unavailable</p>
        <p className="text-gray-300 text-sm mt-1">Please try again later</p>
      </div>
    );
  }

  return <GalleryClient data={galleryData} />;
} 