"use client";

import { useEffect, useState } from "react";
import { usePathname, useSearchParams } from "next/navigation";
import Header from "./Header";
import Footer from "./Footer";
import { getFooterData } from "@/services";

// Pages that should not show the site header and footer
const STANDALONE_ROUTES = ["/courses", "/quiz", "/verify-user", "/activate"];

export default function LayoutShell({ children }) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [footerData, setFooterData] = useState(null);
  const isStandalone = STANDALONE_ROUTES.some((route) => pathname.startsWith(route));
  // Hide header/footer on programmes page when user_id is present (enrollment flow)
  const isProgrammesEnrollFlow = pathname.startsWith("/programmes") && searchParams.get("user_id");

  useEffect(() => {
    let active = true;

    async function loadFooterData() {
      if (isStandalone || isProgrammesEnrollFlow) {
        return;
      }

      try {
        const data = await getFooterData();
        if (active) {
          setFooterData(data);
        }
      } catch (error) {
        if (active) {
          setFooterData(null);
        }
      }
    }

    loadFooterData();

    return () => {
      active = false;
    };
  }, [isStandalone, isProgrammesEnrollFlow]);

  if (isStandalone || isProgrammesEnrollFlow) {
    return <>{children}</>;
  }

  return (
    <>
      <Header />
      {children}
      <Footer data={footerData} />
    </>
  );
}
