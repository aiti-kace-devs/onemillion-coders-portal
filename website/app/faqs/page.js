import { getFaqsData } from "../../services";
import FaqsClient from "./FaqsClient";

export default async function FAQPage() {
  let faqsData = null;
  
  try {
    faqsData = await getFaqsData();
  } catch (error) {
    console.error('Failed to fetch FAQs data:', error);
  }

  // Don't render if no API data
  if (!faqsData) {
    return null;
  }

  return <FaqsClient data={faqsData} />;
}
