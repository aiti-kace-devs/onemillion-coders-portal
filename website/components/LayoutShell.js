"use client";

import { usePathname, useSearchParams } from "next/navigation";
import Header from "./Header";
import Footer from "./Footer";

// Pages that should not show the site header and footer
const STANDALONE_ROUTES = ["/courses", "/quiz"];

export default function LayoutShell({ children, footerData }) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const isStandalone = STANDALONE_ROUTES.some((route) => pathname.startsWith(route));
  // Hide header/footer on programmes page when user_id is present (enrollment flow)
  const isProgrammesEnrollFlow = pathname.startsWith("/programmes") && searchParams.get("user_id");

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
