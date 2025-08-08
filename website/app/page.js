import { getHomepageData, getCategoriesData } from "../services";
import HeroSlider from "../components/HeroSlider";
import AboutSection from "../components/AboutSection";
import CoursesSection from "../components/CoursesSection";
import ImpactSection from "../components/ImpactSection";
import PartnersSection from "../components/PartnersSection";
import TechGhanaSection from "../components/TechGhanaSection";

export const dynamic = 'force-dynamic';

export default async function Home() {
  let homepageData = null;
  let categoriesData = null;

  try {
    [homepageData, categoriesData] = await Promise.all([
      getHomepageData(),
      getCategoriesData()
    ]);
  } catch (error) {
    console.error("Failed to fetch homepage data:", error);
  }

  return (
    <main>
      <HeroSlider
        data={homepageData?.sections?.find((s) => s.name === "Hero")}
      />
      <AboutSection
        data={homepageData?.sections?.find((s) => s.name === "Mahama")}
      />
      <CoursesSection categories={categoriesData} />
      <ImpactSection
        data={homepageData?.sections?.find((s) => s.name === "Metrics-Stories")}
      />
      <PartnersSection
        data={homepageData?.sections?.find((s) => s.name === "Partners")}
      />
      <TechGhanaSection />
    </main>
  );
}
