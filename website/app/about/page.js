import { getAboutData } from "../../services";
import AboutClient from "./AboutClient";


export const dynamic = 'force-dynamic';


export default async function AboutPage() {
  let aboutData = null;
  
  try {
    aboutData = await getAboutData();
  } catch (error) {
    console.error('Failed to fetch about data:', error);
  }

  // Don't render if no API data
  if (!aboutData) {
    return null;
  }

  return <AboutClient data={aboutData} />;
}
