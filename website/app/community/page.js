import { getTestimonialsData } from "../../services";
import CommunityClient from "./CommunityClient";

export const dynamic = "force-dynamic";

export default async function CommunityPage() {
  let testimonialsData = null;

  try {
    testimonialsData = await getTestimonialsData();
  } catch (error) {
    console.error("Failed to fetch testimonials:", error);
  }

  if (!testimonialsData) {
    return null;
  }

  return <CommunityClient data={testimonialsData} />;
}
