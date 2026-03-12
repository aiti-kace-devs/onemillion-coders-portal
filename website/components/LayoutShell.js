"use client";

import { usePathname } from "next/navigation";
import Header from "./Header";
import Footer from "./Footer";

// Pages that should not show the site header and footer
const STANDALONE_ROUTES = ["/courses", "/quiz"];

export default function LayoutShell({ children, footerData }) {
  const pathname = usePathname();
  const isStandalone = STANDALONE_ROUTES.some((route) => pathname.startsWith(route));

  if (isStandalone) {
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
