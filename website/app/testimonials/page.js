import { getTestimonialsData } from "../../services";
import TestimonialsClient from "./TestimonialsClient";

export default async function TestimonialsPage() {
  let testimonialsData = null;
  
  try {
    testimonialsData = await getTestimonialsData();
  } catch (error) {
    console.error('Failed to fetch testimonials data:', error);
  }

  // Don't render if no API data
  if (!testimonialsData) {
    return null;
  }

  return <TestimonialsClient data={testimonialsData} />;
} 