import { getGalleryData } from "../../services";
import GalleryClient from "./GalleryClient";

export default async function GalleryPage() {
  let galleryData = null;
  
  try {
    galleryData = await getGalleryData();
  } catch (error) {
    console.error('Failed to fetch gallery data:', error);
  }

  // Don't render if no API data
  if (!galleryData) {
    return null;
  }

  return <GalleryClient data={galleryData} />;
} 