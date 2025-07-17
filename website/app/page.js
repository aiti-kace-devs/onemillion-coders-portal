"use client";

import HeroSlider from "../components/HeroSlider";
import AboutSection from "../components/AboutSection";
import CoursesSection from "../components/CoursesSection";
import ImpactSection from "../components/ImpactSection";
import PartnersSection from "../components/PartnersSection";
import TechGhanaSection from "../components/TechGhanaSection";

export default function Home() {
  return (
    <main>
      <HeroSlider />
      {/* <div className="section-divider"></div> */}
      <AboutSection />
      {/* <div className="section-divider"></div> */}
      <CoursesSection />
      {/* <div className="section-divider"></div> */}
      <ImpactSection />
      {/* <div className="section-divider"></div> */}
      <PartnersSection />
      {/* <div className="section-divider"></div> */}
      <TechGhanaSection />
    </main>
  );
}                                  
